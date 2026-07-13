<?php

declare(strict_types=1);

use App\Enums\CompletionStatus;
use App\Jobs\NotifyValidationWorkflowJob;
use App\Models\Quest;
use App\Models\Tenant;
use App\Models\User;
use App\Support\GeoPoint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Storage::fake('photos');
    Queue::fake();

    $this->tenant = Tenant::factory()->create();
    $this->quest = Quest::factory()->create([ // photo_ai by default
        'tenant_id' => $this->tenant->id,
        'location' => new GeoPoint(43.3853, -8.4064),
        'geofence_radius_m' => 50,
    ]);
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    Sanctum::actingAs($this->user);
});

it('stores the photo, creates a pending completion and dispatches the job', function () {
    $response = $this->post("/api/quests/{$this->quest->id}/submit", [
        'lat' => 43.3853,
        'lng' => -8.4064,
        'photo' => UploadedFile::fake()->create('quest.jpg', 200, 'image/jpeg'),
    ], ['Accept' => 'application/json']);

    $response->assertStatus(202)
        ->assertJsonPath('completion.status', 'pending');

    $completion = $this->user->completions()->first();
    expect($completion)->not->toBeNull()
        ->and($completion->status)->toBe(CompletionStatus::Pending)
        ->and($completion->photo_path)->not->toBeNull();

    Storage::disk('photos')->assertExists($completion->photo_path);
    Queue::assertPushed(NotifyValidationWorkflowJob::class);
});

it('rejects a submit outside the geofence and stores nothing', function () {
    $this->post("/api/quests/{$this->quest->id}/submit", [
        'lat' => 43.30,
        'lng' => -8.30,
        'photo' => UploadedFile::fake()->create('quest.jpg', 200, 'image/jpeg'),
    ], ['Accept' => 'application/json'])->assertStatus(422)
        ->assertJsonPath('error_code', 'outside_geofence');

    expect($this->user->completions()->count())->toBe(0);
    Queue::assertNotPushed(NotifyValidationWorkflowJob::class);
});
