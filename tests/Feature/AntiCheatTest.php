<?php

declare(strict_types=1);

use App\Models\Quest;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserLocationHistory;
use App\Services\AntiCheatService;
use App\Support\GeoPoint;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
});

it('allows the first location with no history', function () {
    $result = app(AntiCheatService::class)->check(
        $this->user,
        new GeoPoint(43.36, -8.41),
        now(),
    );

    expect($result->allowed)->toBeTrue();
});

it('rejects an implausible jump from the last recorded point', function () {
    UserLocationHistory::create([
        'user_id' => $this->user->id,
        'location' => new GeoPoint(43.3600, -8.4100), // A Coruña
        'recorded_at' => now()->subSeconds(30),
    ]);

    // 30s later in Madrid -> way over 150 km/h
    $result = app(AntiCheatService::class)->check(
        $this->user,
        new GeoPoint(40.4168, -3.7038),
        now(),
    );

    expect($result->allowed)->toBeFalse()
        ->and($result->reason)->toBe('implausible_speed');
});

it('allows a slow, plausible move', function () {
    UserLocationHistory::create([
        'user_id' => $this->user->id,
        'location' => new GeoPoint(43.3600, -8.4100),
        'recorded_at' => now()->subSeconds(120),
    ]);

    // ~150 m in 120s -> ~4.5 km/h
    $result = app(AntiCheatService::class)->check(
        $this->user,
        new GeoPoint(43.3613, -8.4100),
        now(),
    );

    expect($result->allowed)->toBeTrue();
});

it('blocks a check-in via the API after a teleport', function () {
    $quest = Quest::factory()->checkin()->create([
        'tenant_id' => $this->tenant->id,
        'location' => new GeoPoint(40.4168, -3.7038),
        'geofence_radius_m' => 50,
    ]);

    UserLocationHistory::create([
        'user_id' => $this->user->id,
        'location' => new GeoPoint(43.3600, -8.4100),
        'recorded_at' => now()->subSeconds(20),
    ]);

    Sanctum::actingAs($this->user);

    $this->postJson("/api/quests/{$quest->id}/checkin", [
        'lat' => 40.4168,
        'lng' => -3.7038,
    ])->assertStatus(422)->assertJsonPath('error_code', 'implausible_speed');
});
