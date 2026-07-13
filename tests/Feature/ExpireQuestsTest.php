<?php

declare(strict_types=1);

use App\Enums\QuestStatus;
use App\Models\Quest;
use App\Models\Tenant;
use App\Support\GeoPoint;

it('marks past-expiry active quests as expired', function () {
    $tenant = Tenant::factory()->create();

    $expired = Quest::factory()->create([
        'tenant_id' => $tenant->id,
        'location' => new GeoPoint(43.37, -8.40),
        'status' => QuestStatus::Active,
        'expires_at' => now()->subDay(),
    ]);

    $active = Quest::factory()->create([
        'tenant_id' => $tenant->id,
        'location' => new GeoPoint(43.37, -8.40),
        'status' => QuestStatus::Active,
        'expires_at' => now()->addMonth(),
    ]);

    $this->artisan('quests:expire')->assertSuccessful();

    expect($expired->fresh()->status)->toBe(QuestStatus::Expired)
        ->and($active->fresh()->status)->toBe(QuestStatus::Active);
});
