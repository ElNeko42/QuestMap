<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Immutable lat/lng value object used throughout the app for geography(Point)
 * columns. Serializes to {"lat": .., "lng": ..} in API responses.
 */
final readonly class GeoPoint implements JsonSerializable
{
    public function __construct(
        public float $lat,
        public float $lng,
    ) {
        if ($lat < -90.0 || $lat > 90.0) {
            throw new InvalidArgumentException("Invalid latitude: {$lat}");
        }
        if ($lng < -180.0 || $lng > 180.0) {
            throw new InvalidArgumentException("Invalid longitude: {$lng}");
        }
    }

    /**
     * Build from a loose array shape: ['lat'=>.., 'lng'=>..] (also accepts
     * 'lon'/'longitude'/'latitude' keys) or [lng, lat] pair.
     */
    public static function fromArray(array $data): self
    {
        $lat = $data['lat'] ?? $data['latitude'] ?? $data[1] ?? null;
        $lng = $data['lng'] ?? $data['lon'] ?? $data['longitude'] ?? $data[0] ?? null;

        if ($lat === null || $lng === null) {
            throw new InvalidArgumentException('GeoPoint requires lat and lng.');
        }

        return new self((float) $lat, (float) $lng);
    }

    /**
     * Well-Known Text for PostGIS. Note: WKT is "POINT(lng lat)" (X Y order).
     */
    public function toWkt(): string
    {
        return sprintf('POINT(%.8F %.8F)', $this->lng, $this->lat);
    }

    /**
     * Great-circle distance to another point in meters (Haversine). Used by the
     * anti-cheat speed check; PostGIS ST_Distance is used for authoritative
     * geofence checks server-side.
     */
    public function distanceMeters(self $other): float
    {
        $earthRadius = 6371000.0; // meters

        $lat1 = deg2rad($this->lat);
        $lat2 = deg2rad($other->lat);
        $dLat = deg2rad($other->lat - $this->lat);
        $dLng = deg2rad($other->lng - $this->lng);

        $a = sin($dLat / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * asin(min(1.0, sqrt($a)));
    }

    /**
     * @return array{lat: float, lng: float}
     */
    public function toArray(): array
    {
        return ['lat' => $this->lat, 'lng' => $this->lng];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
