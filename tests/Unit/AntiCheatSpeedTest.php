<?php

declare(strict_types=1);

use App\Services\AntiCheatService;
use App\Support\GeoPoint;

beforeEach(function () {
    $this->service = new AntiCheatService();
});

it('computes a plausible walking/driving speed', function () {
    // ~1.11 km apart (0.01 deg latitude) over 60s -> ~66 km/h
    $from = new GeoPoint(43.3600, -8.4100);
    $to = new GeoPoint(43.3700, -8.4100);

    $speed = $this->service->impliedSpeedKmh($from, $to, 60);

    expect($speed)->toBeGreaterThan(60.0)->toBeLessThan(72.0);
});

it('flags teleporting as very high speed', function () {
    // A Coruña -> Madrid (~1 deg lat is huge) in 10 seconds
    $from = new GeoPoint(43.3600, -8.4100);
    $to = new GeoPoint(40.4168, -3.7038);

    $speed = $this->service->impliedSpeedKmh($from, $to, 10);

    expect($speed)->toBeGreaterThan(150.0);
});

it('returns zero speed for non-positive elapsed time', function () {
    $p = new GeoPoint(43.36, -8.41);

    expect($this->service->impliedSpeedKmh($p, $p, 0))->toBe(0.0);
});

it('measures distance between points (haversine)', function () {
    $from = new GeoPoint(43.3600, -8.4100);
    $to = new GeoPoint(43.3700, -8.4100);

    // 0.01 deg latitude ~ 1111 m
    expect($from->distanceMeters($to))->toBeGreaterThan(1100.0)->toBeLessThan(1120.0);
});
