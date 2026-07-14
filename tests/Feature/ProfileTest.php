<?php

declare(strict_types=1);

use App\Models\Quest;
use App\Models\Tenant;
use App\Models\User;
use App\Support\GeoPoint;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Original',
        'password' => Hash::make('password'),
    ]);
    Sanctum::actingAs($this->user);
});

it('updates the profile name', function () {
    $this->patchJson('/api/me', ['name' => 'Nuevo Nombre'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Nuevo Nombre');

    expect($this->user->fresh()->name)->toBe('Nuevo Nombre');
});

it('rejects an email already taken by another user', function () {
    User::factory()->create(['email' => 'taken@questmap.test']);

    $this->patchJson('/api/me', ['email' => 'taken@questmap.test'])
        ->assertStatus(422);
});

it('changes the password with the correct current password', function () {
    $this->putJson('/api/me/password', [
        'current_password' => 'password',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertOk();

    expect(Hash::check('newpassword123', $this->user->fresh()->password))->toBeTrue();
});

it('rejects a password change with a wrong current password', function () {
    $this->putJson('/api/me/password', [
        'current_password' => 'wrong',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertStatus(422);

    expect(Hash::check('password', $this->user->fresh()->password))->toBeTrue();
});

it('sets and clears the target quest', function () {
    $quest = Quest::factory()->create([
        'tenant_id' => $this->tenant->id,
        'location' => new GeoPoint(43.37, -8.40),
    ]);

    $this->postJson('/api/me/target', ['quest_id' => $quest->id])
        ->assertOk()
        ->assertJsonPath('data.target_quest_id', $quest->id)
        ->assertJsonPath('data.target_quest.title', $quest->title);

    $this->deleteJson('/api/me/target')
        ->assertOk()
        ->assertJsonPath('data.target_quest_id', null);

    expect($this->user->fresh()->target_quest_id)->toBeNull();
});
