<?php

declare(strict_types=1);

use App\Services\HmacService;

beforeEach(function () {
    $this->hmac = new HmacService();
});

it('signs and verifies a payload', function () {
    $secret = 'topsecret';
    $ts = (string) time();
    $body = '{"a":1}';

    $sig = $this->hmac->sign($secret, $ts, $body);

    expect($this->hmac->verify($secret, $ts, $body, $sig))->toBeTrue();
});

it('rejects a tampered body', function () {
    $secret = 'topsecret';
    $ts = (string) time();
    $sig = $this->hmac->sign($secret, $ts, '{"a":1}');

    expect($this->hmac->verify($secret, $ts, '{"a":2}', $sig))->toBeFalse();
});

it('rejects a wrong secret', function () {
    $ts = (string) time();
    $body = '{"a":1}';
    $sig = $this->hmac->sign('right', $ts, $body);

    expect($this->hmac->verify('wrong', $ts, $body, $sig))->toBeFalse();
});

it('accepts timestamps within skew and rejects old ones', function () {
    expect($this->hmac->timestampWithinSkew(time(), 300))->toBeTrue()
        ->and($this->hmac->timestampWithinSkew(time() - 301, 300))->toBeFalse()
        ->and($this->hmac->timestampWithinSkew('not-a-number', 300))->toBeFalse();
});
