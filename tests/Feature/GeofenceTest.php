<?php

declare(strict_types=1);

use App\Models\Quest;
use App\Models\Tenant;
use App\Models\User;
use App\Support\GeoPoint;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create([
        'center_point' => new GeoPoint(43.3623, -8.4115),
    ]);

    // Check-in quest at Plaza de María Pita, 50 m geofence.
    $this->quest = Quest::factory()->checkin()->create([
        'tenant_id' => $this->tenant->id,
        'location' => new GeoPoint(43.3703, -8.3959),
        'geofence_radius_m' => 50,
        'xp_reward' => 80,
    ]);

    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    Sanctum::actingAs($this->user);
});

it('allows a check-in inside the geofence and awards xp', function () {
    $response = $this->postJson("/api/quests/{$this->quest->id}/checkin", [
        'lat' => 43.3703,
        'lng' => -8.3959,
    ]);

    $response->assertCreated()
        ->assertJsonPath('completion.status', 'approved')
        ->assertJsonPath('completion.xp_awarded', 80);

    expect($this->user->fresh()->xp)->toBe(80);
});

it('rejects a check-in outside the geofence with 422', function () {
    $response = $this->postJson("/api/quests/{$this->quest->id}/checkin", [
        'lat' => 43.3800, // ~1 km away
        'lng' => -8.4100,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('error_code', 'outside_geofence');

    expect($this->user->fresh()->xp)->toBe(0);
});

it('rejects a check-in just outside the radius (boundary)', function () {
    // ~90 m north of the quest (radius is 50 m).
    $response = $this->postJson("/api/quests/{$this->quest->id}/checkin", [
        'lat' => 43.37111,
        'lng' => -8.3959,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('error_code', 'outside_geofence');
});

it('does not let a photo quest be completed by check-in', function () {
    $photoQuest = Quest::factory()->create([
        'tenant_id' => $this->tenant->id,
        'location' => new GeoPoint(43.3703, -8.3959),
    ]);

    $this->postJson("/api/quests/{$photoQuest->id}/checkin", [
        'lat' => 43.3703,
        'lng' => -8.3959,
    ])->assertStatus(422)->assertJsonPath('error_code', 'wrong_validation_type');
});
