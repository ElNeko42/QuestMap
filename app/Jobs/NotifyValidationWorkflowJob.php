<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CompletionStatus;
use App\Models\QuestCompletion;
use App\Services\HmacService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Fire-and-forget notification to the n8n validation workflow. Sends the
 * completion id, a temporary signed URL to the photo, the quest's validation
 * prompt and a callback URL. The payload is HMAC-signed (X-Signature) so n8n can
 * verify authenticity; n8n is expected to reply 200 immediately and call back
 * asynchronously when the vision AI finishes.
 */
class NotifyValidationWorkflowJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public int $completionId) {}

    public function handle(HmacService $hmac): void
    {
        $completion = QuestCompletion::with('quest')->find($this->completionId);

        if ($completion === null || $completion->status !== CompletionStatus::Pending) {
            return; // resolved or gone — nothing to do
        }

        $webhook = config('questmap.n8n.validation_webhook_url');
        if (empty($webhook)) {
            Log::warning('N8N_VALIDATION_WEBHOOK_URL not configured; skipping validation dispatch', [
                'completion_id' => $completion->id,
            ]);

            return;
        }

        $payload = [
            'completion_id' => $completion->id,
            'quest_id' => $completion->quest_id,
            'photo_url' => $this->signedPhotoUrl($completion->photo_path),
            'validation_prompt' => $completion->quest?->validation_prompt,
            'callback_url' => $this->callbackUrl($completion->id),
        ];

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $timestamp = (string) time();
        $signature = $hmac->sign(
            (string) config('questmap.n8n.webhook_secret'),
            $timestamp,
            $body,
        );

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Timestamp' => $timestamp,
            'X-Signature' => $signature,
        ])->withBody($body, 'application/json')
            ->timeout(10)
            ->post($webhook);

        if (! $response->successful()) {
            Log::warning('n8n validation webhook returned non-2xx', [
                'completion_id' => $completion->id,
                'status' => $response->status(),
            ]);

            // Let the queue retry (throwing triggers a retry within $tries).
            $response->throw();
        }
    }

    private function signedPhotoUrl(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        $ttl = (int) config('questmap.photo_url_ttl_minutes');

        // Sign against the public base URL (e.g. host.docker.internal) so the
        // signature matches the host n8n actually calls, not the internal
        // container host.
        $original = config('app.url');
        URL::forceRootUrl((string) config('questmap.public_url'));

        try {
            return URL::temporarySignedRoute(
                'internal.photo',
                now()->addMinutes($ttl),
                ['completion' => $this->completionId],
            );
        } finally {
            URL::forceRootUrl($original);
        }
    }

    private function callbackUrl(int $completionId): string
    {
        $base = rtrim((string) config('questmap.public_url'), '/');

        return "{$base}/api/internal/completions/{$completionId}/validation-callback";
    }
}
