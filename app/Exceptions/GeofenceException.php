<?php

declare(strict_types=1);

namespace App\Exceptions;

class GeofenceException extends ApiException
{
    public static function forDistance(float $distanceMeters, int $radiusMeters): self
    {
        return new self(
            'Estás demasiado lejos para completar esta misión. Acércate al punto indicado.',
            'outside_geofence',
            422,
            [
                'distance_m' => round($distanceMeters, 1),
                'required_radius_m' => $radiusMeters,
            ],
        );
    }
}
