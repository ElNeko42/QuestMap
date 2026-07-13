<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Support\GeoPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\UserLocationHistory>
 */
class UserLocationHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'location' => new GeoPoint(43.3623, -8.4115),
            'recorded_at' => now(),
        ];
    }
}
