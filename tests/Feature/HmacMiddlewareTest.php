<?php

declare(strict_types=1);

use App\Models\Tenant;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create(['slug' => 'a-coruna']);

    $this->body = json_encode([
        'tenant_slug' => 'a-coruna',
        'quests' => [[
            'title' => 'Test Quest',
            'description' => 'desc',
            'category' => 'history',
            'lat' => 43.37,
            'lng' => -8.40,
            'validation_type' => 'checkin',
            'xp_reward' => 50,
        ]],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
});

/**
 * POST a raw (already-encoded) body to the internal batch route with the given
 * server vars. Sending raw content keeps the signed bytes intact.
 */
function postRaw(object $test, array $server, string $body)
{
    return $test->call('POST', '/api/internal/quests/batch', [], [], [], $server, $body);
}

it('accepts a correctly signed internal request', function () {
    postRaw($this, internalHmacHeaders($this->body), $this->body)
        ->assertCreated()
        ->assertJsonPath('created_count', 1);
});

it('rejects a request with no signature', function () {
    postRaw($this, ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'], $this->body)
        ->assertStatus(401)
        ->assertJsonPath('error_code', 'invalid_signature');
});

it('rejects a request signed with the wrong secret', function () {
    postRaw($this, internalHmacHeaders($this->body, 'wrong-secret'), $this->body)
        ->assertStatus(401);
});

it('rejects a tampered body under a valid signature', function () {
    $server = internalHmacHeaders($this->body);
    $tampered = str_replace('50', '99999', $this->body);

    postRaw($this, $server, $tampered)->assertStatus(401);
});

it('rejects a stale timestamp (replay protection)', function () {
    postRaw($this, internalHmacHeaders($this->body, null, time() - 3600), $this->body)
        ->assertStatus(401);
});
