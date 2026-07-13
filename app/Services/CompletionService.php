<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CompletionStatus;
use App\Enums\ValidationType;
use App\Exceptions\AntiCheatException;
use App\Exceptions\GeofenceException;
use App\Exceptions\QuestClosedException;
use App\Jobs\NotifyValidationWorkflowJob;
use App\Models\Quest;
use App\Models\QuestCompletion;
use App\Models\User;
use App\Support\GeoPoint;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;

class CompletionService
{
    public function __construct(
        private readonly QuestService $questService,
        private readonly AntiCheatService $antiCheat,
        private readonly XpService $xpService,
    ) {}

    /**
     * Complete a check-in quest. Runs anti-cheat + server-side geofence, records
     * the location, then approves the completion and awards XP immediately.
     */
    public function checkin(User $user, Quest $quest, GeoPoint $point, CarbonInterface $at): QuestCompletion
    {
        if ($quest->validation_type !== ValidationType::Checkin) {
            throw QuestClosedException::wrongValidationType('photo');
        }

        $this->guardMovementAndGeofence($user, $quest, $point, $at);

        $completion = $this->openOrReuseCompletion($user, $quest, $point, $at);

        // Check-in quests are validated purely by presence: approve now.
        $completion->status = CompletionStatus::Pending;
        $completion->save();

        $this->xpService->awardForApproval($completion);
        $completion->refresh();

        return $completion;
    }

    /**
     * Submit a photo for a photo_ai quest. Stores the photo, creates a pending
     * completion and dispatches the async validation job. XP is awarded later by
     * the n8n callback.
     */
    public function submitPhoto(User $user, Quest $quest, GeoPoint $point, UploadedFile $photo, CarbonInterface $at): QuestCompletion
    {
        if ($quest->validation_type !== ValidationType::PhotoAi) {
            throw QuestClosedException::wrongValidationType('checkin');
        }

        $this->guardMovementAndGeofence($user, $quest, $point, $at);

        $path = $photo->store("quests/{$quest->id}", 'photos');

        $completion = $this->openOrReuseCompletion($user, $quest, $point, $at);
        $completion->photo_path = $path;
        $completion->status = CompletionStatus::Pending;
        $completion->ai_validation_result = null;
        $completion->ai_confidence = null;
        $completion->xp_awarded = 0;
        $completion->save();

        NotifyValidationWorkflowJob::dispatch($completion->id);

        return $completion;
    }

    /**
     * Apply an AI validation verdict to a completion. Idempotent: if the
     * completion is no longer pending, this is a no-op.
     */
    public function applyValidation(
        QuestCompletion $completion,
        bool $valid,
        float $confidence,
        ?string $reason,
    ): QuestCompletion {
        if ($completion->status !== CompletionStatus::Pending) {
            return $completion; // already resolved — idempotent no-op
        }

        $completion->ai_confidence = $confidence;
        $completion->ai_validation_result = [
            'valid' => $valid,
            'confidence' => $confidence,
            'reason' => $reason,
            'received_at' => now()->toIso8601String(),
        ];

        $approveAt = (float) config('questmap.confidence.approve');
        $reviewAt = (float) config('questmap.confidence.review');

        if ($valid && $confidence >= $approveAt) {
            // awardForApproval persists status + xp inside its own transaction.
            $completion->save();
            $this->xpService->awardForApproval($completion);

            return $completion->refresh();
        }

        if ($valid && $confidence >= $reviewAt) {
            $completion->status = CompletionStatus::ManualReview;
        } else {
            $completion->status = CompletionStatus::Rejected;
        }

        $completion->save();

        return $completion;
    }

    private function guardMovementAndGeofence(User $user, Quest $quest, GeoPoint $point, CarbonInterface $at): void
    {
        if (! $quest->isOpenForCompletion()) {
            throw QuestClosedException::make();
        }

        $result = $this->antiCheat->check($user, $point, $at);
        if (! $result->allowed) {
            throw AntiCheatException::implausibleSpeed($result->speedKmh ?? 0.0);
        }

        // Record the accepted location (history + last_location) before the
        // geofence decision so we always keep the movement trail.
        $this->antiCheat->record($user, $point, $at);

        if (! $this->questService->isWithinGeofence($quest, $point)) {
            $distance = $this->questService->distanceMeters($quest, $point);
            throw GeofenceException::forDistance($distance, $quest->geofence_radius_m);
        }
    }

    /**
     * Fetch the user's existing completion for the quest, or make a fresh one.
     * A previously rejected completion may be retried; an approved/manual one
     * cannot.
     */
    private function openOrReuseCompletion(User $user, Quest $quest, GeoPoint $point, CarbonInterface $at): QuestCompletion
    {
        /** @var QuestCompletion|null $existing */
        $existing = QuestCompletion::query()
            ->where('quest_id', $quest->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing !== null) {
            if ($existing->status !== CompletionStatus::Rejected) {
                throw QuestClosedException::alreadyCompleted();
            }

            $existing->fill([
                'submitted_at' => $at,
                'location_at_submit' => $point,
            ]);

            return $existing;
        }

        return new QuestCompletion([
            'quest_id' => $quest->id,
            'user_id' => $user->id,
            'submitted_at' => $at,
            'location_at_submit' => $point,
            'status' => CompletionStatus::Pending,
        ]);
    }
}
