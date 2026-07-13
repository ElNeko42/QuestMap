<?php

declare(strict_types=1);

use App\Enums\CompletionStatus;
use App\Models\Quest;
use App\Models\QuestCompletion;
use App\Models\Tenant;
use App\Models\User;
use App\Support\GeoPoint;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->quest = Quest::factory()->create([
        'tenant_id' => $this->tenant->id,
        'xp_reward' => 150,
        'location' => new GeoPoint(43.37, -8.40),
    ]);
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id, 'xp' => 0, 'level' => 1]);

    $this->completion = QuestCompletion::factory()->create([
        'quest_id' => $this->quest->id,
        'user_id' => $this->user->id,
        'status' => CompletionStatus::Pending,
        'photo_path' => 'quests/1/x.jpg',
    ]);
});

function callback(object $test, int $completionId, array $payload)
{
    $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    return $test->call(
        'POST',
        "/api/internal/completions/{$completionId}/validation-callback",
        [], [], [],
        internalHmacHeaders($body),
        $body,
    );
}

it('approves and awards xp when confidence is high', function () {
    callback($this, $this->completion->id, ['valid' => true, 'confidence' => 0.95, 'reason' => 'match'])
        ->assertOk()
        ->assertJsonPath('completion.status', 'approved')
        ->assertJsonPath('completion.xp_awarded', 150);

    expect($this->user->fresh()->xp)->toBe(150)
        ->and($this->quest->fresh()->completions_count)->toBe(1);
});

it('sends to manual review for mid confidence', function () {
    callback($this, $this->completion->id, ['valid' => true, 'confidence' => 0.65, 'reason' => 'unsure'])
        ->assertOk()
        ->assertJsonPath('completion.status', 'manual_review');

    expect($this->user->fresh()->xp)->toBe(0);
});

it('rejects for low confidence', function () {
    callback($this, $this->completion->id, ['valid' => true, 'confidence' => 0.2, 'reason' => 'no match'])
        ->assertOk()
        ->assertJsonPath('completion.status', 'rejected');

    expect($this->user->fresh()->xp)->toBe(0);
});

it('rejects when valid is false regardless of confidence', function () {
    callback($this, $this->completion->id, ['valid' => false, 'confidence' => 0.99, 'reason' => 'wrong subject'])
        ->assertOk()
        ->assertJsonPath('completion.status', 'rejected');
});

it('is idempotent: a replayed approval does not double xp', function () {
    callback($this, $this->completion->id, ['valid' => true, 'confidence' => 0.95, 'reason' => 'match'])->assertOk();
    callback($this, $this->completion->id, ['valid' => true, 'confidence' => 0.95, 'reason' => 'match'])
        ->assertOk()
        ->assertJsonPath('completion.status', 'approved')
        ->assertJsonPath('completion.xp_awarded', 150);

    expect($this->user->fresh()->xp)->toBe(150)
        ->and($this->quest->fresh()->completions_count)->toBe(1);
});
