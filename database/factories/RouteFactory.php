<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Route>
 */
class RouteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'title' => $this->faker->sentence(3),
            'theme' => $this->faker->randomElement(['history', 'food', 'nature', 'art']),
            'description' => $this->faker->paragraph(),
            'quest_ids' => [],
            'estimated_minutes' => $this->faker->numberBetween(60, 240),
        ];
    }
}
