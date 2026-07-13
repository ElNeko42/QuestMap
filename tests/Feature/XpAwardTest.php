<?php

declare(strict_types=1);

use App\Enums\CompletionStatus;
use App\Enums\QuestStatus;
use App\Models\Quest;
use App\Models\QuestCompletion;
use App\Models\Tenant;
use App\Models\User;
use App\Services\XpService;
use App\Support\GeoPoint;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->xp = app(XpService::class);
});

it('awards xp, recomputes level, and increments the quest count', function () {
    $quest = Quest::factory()->create([
        'tenant_id' => $this->tenant->id,
        'xp_reward' => 150,
        'location' => new GeoPoint(43.37, -8.40),
    ]);
    $user = User::factory()->create(['tenant_id' => $this->tenant->id, 'xp' => 300, 'level' => 2]);

    $completion = QuestCompletion::factory()->create([
        'quest_id' => $quest->id,
        'user_id' => $user->id,
        'status' => CompletionStatus::Pending,
    ]);

    $awarded = $this->xp->awardForApproval($completion);

    expect($awarded)->toBe(150)
        ->and($user->fresh()->xp)->toBe(450)
        ->and($user->fresh()->level)->toBe(3) // sqrt(450/100)=2.12 -> floor+1 = 3
        ->and($quest->fresh()->completions_count)->toBe(1)
        ->and($completion->fresh()->status)->toBe(CompletionStatus::Approved);
});

it('exhausts a quest when max_completions is reached', function () {
    $quest = Quest::factory()->create([
        'tenant_id' => $this->tenant->id,
        'xp_reward' => 50,
        'max_completions' => 1,
        'completions_count' => 0,
        'location' => new GeoPoint(43.37, -8.40),
    ]);
    $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

    $completion = QuestCompletion::factory()->create([
        'quest_id' => $quest->id,
        'user_id' => $user->id,
        'status' => CompletionStatus::Pending,
    ]);

    $this->xp->awardForApproval($completion);

    expect($quest->fresh()->status)->toBe(QuestStatus::Exhausted)
        ->and($quest->fresh()->completions_count)->toBe(1);
});

it('is a no-op when the completion is already approved', function () {
    $quest = Quest::factory()->create([
        'tenant_id' => $this->tenant->id,
        'xp_reward' => 100,
        'location' => new GeoPoint(43.37, -8.40),
    ]);
    $user = User::factory()->create(['tenant_id' => $this->tenant->id, 'xp' => 0]);

    $completion = QuestCompletion::factory()->create([
        'quest_id' => $quest->id,
        'user_id' => $user->id,
        'status' => CompletionStatus::Pending,
    ]);

    $this->xp->awardForApproval($completion);
    $again = $this->xp->awardForApproval($completion->fresh());

    expect($again)->toBe(0)
        ->and($user->fresh()->xp)->toBe(100)
        ->and($quest->fresh()->completions_count)->toBe(1);
});
