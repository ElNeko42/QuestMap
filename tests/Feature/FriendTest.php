<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->alba = User::factory()->create(['tenant_id' => $this->tenant->id, 'email' => 'alba@t.test', 'xp' => 1000]);
    $this->brais = User::factory()->create(['tenant_id' => $this->tenant->id, 'email' => 'brais@t.test', 'xp' => 500]);
});

it('sends a friend request by email', function () {
    Sanctum::actingAs($this->alba);

    $this->postJson('/api/friends/request', ['email' => 'brais@t.test'])
        ->assertCreated()
        ->assertJsonPath('status', 'pending');

    // Brais sees the incoming request
    Sanctum::actingAs($this->brais);
    $this->getJson('/api/friends/requests')
        ->assertOk()
        ->assertJsonPath('data.0.from.id', $this->alba->id);
});

it('lets the addressee accept and then both see each other as friends', function () {
    Sanctum::actingAs($this->alba);
    $this->postJson('/api/friends/request', ['email' => 'brais@t.test'])->assertCreated();

    Sanctum::actingAs($this->brais);
    $this->postJson("/api/friends/{$this->alba->id}/accept")->assertOk();

    // Both directions list the other, with comparison fields
    $this->getJson('/api/friends')
        ->assertOk()
        ->assertJsonPath('data.0.id', $this->alba->id)
        ->assertJsonPath('data.0.xp', 1000);

    Sanctum::actingAs($this->alba);
    $this->getJson('/api/friends')
        ->assertOk()
        ->assertJsonPath('data.0.id', $this->brais->id);
});

it('auto-accepts a reciprocal request', function () {
    Sanctum::actingAs($this->alba);
    $this->postJson('/api/friends/request', ['email' => 'brais@t.test'])->assertCreated();

    // Brais requests Alba back -> should auto-accept
    Sanctum::actingAs($this->brais);
    $this->postJson('/api/friends/request', ['email' => 'alba@t.test'])
        ->assertCreated()
        ->assertJsonPath('status', 'accepted');

    $this->getJson('/api/friends')->assertJsonPath('data.0.id', $this->alba->id);
});

it('cannot befriend yourself', function () {
    Sanctum::actingAs($this->alba);
    $this->postJson('/api/friends/request', ['email' => 'alba@t.test'])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'self_friend');
});

it('returns 404 for an unknown email', function () {
    Sanctum::actingAs($this->alba);
    $this->postJson('/api/friends/request', ['email' => 'nobody@t.test'])
        ->assertStatus(404)
        ->assertJsonPath('error_code', 'user_not_found');
});

it('removes a friendship', function () {
    Sanctum::actingAs($this->alba);
    $this->postJson('/api/friends/request', ['email' => 'brais@t.test'])->assertCreated();
    Sanctum::actingAs($this->brais);
    $this->postJson("/api/friends/{$this->alba->id}/accept")->assertOk();

    Sanctum::actingAs($this->alba);
    $this->deleteJson("/api/friends/{$this->brais->id}")->assertOk();
    $this->getJson('/api/friends')->assertJsonCount(0, 'data');
});
