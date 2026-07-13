<?php

declare(strict_types=1);

namespace App\Exceptions;

class AntiCheatException extends ApiException
{
    public static function implausibleSpeed(float $speedKmh): self
    {
        return new self(
            'Movimiento imposible detectado. Comprueba tu ubicación e inténtalo de nuevo.',
            'implausible_speed',
            422,
            ['speed_kmh' => round($speedKmh, 1)],
        );
    }
}
