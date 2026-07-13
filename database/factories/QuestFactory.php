<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CreatorType;
use App\Enums\QuestStatus;
use App\Enums\ValidationType;
use App\Models\Tenant;
use App\Support\GeoPoint;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Quest>
 */
class QuestFactory extends Factory
{
    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(3);

        return [
            'tenant_id' => Tenant::factory(),
            'creator_type' => CreatorType::Ai,
            'title' => $title,
            'description' => $this->faker->paragraph(),
            'category' => $this->faker->randomElement(['architecture', 'food', 'history', 'nature', 'street_art']),
            'location' => new GeoPoint(
                (float) $this->faker->latitude(43.34, 43.38),
                (float) $this->faker->longitude(-8.43, -8.38),
            ),
            'geofence_radius_m' => 50,
            'validation_type' => ValidationType::PhotoAi,
            'validation_prompt' => $this->faker->sentence(),
            'xp_reward' => $this->faker->randomElement([50, 100, 150, 200]),
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonths(3),
            'max_completions' => null,
            'completions_count' => 0,
            'dedup_hash' => hash('sha256', Str::random(32)),
            'status' => QuestStatus::Active,
        ];
    }

    public function checkin(): static
    {
        return $this->state(fn () => [
            'validation_type' => ValidationType::Checkin,
            'validation_prompt' => null,
        ]);
    }
}
