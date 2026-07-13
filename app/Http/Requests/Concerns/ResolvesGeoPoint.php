<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Support\GeoPoint;

trait ResolvesGeoPoint
{
    /**
     * Latitude/longitude validation rules for a required coordinate pair.
     *
     * @return array<string, array<int, string>>
     */
    protected function geoRules(): array
    {
        return [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function geoPoint(): GeoPoint
    {
        return new GeoPoint(
            (float) $this->validated('lat'),
            (float) $this->validated('lng'),
        );
    }
}
