<?php

declare(strict_types=1);

namespace App\Services;

final readonly class AntiCheatResult
{
    public function __construct(
        public bool $allowed,
        public ?float $speedKmh = null,
        public ?string $reason = null,
    ) {}

    public static function allow(?float $speedKmh = null): self
    {
        return new self(true, $speedKmh);
    }

    public static function reject(float $speedKmh, string $reason): self
    {
        return new self(false, $speedKmh, $reason);
    }
}
