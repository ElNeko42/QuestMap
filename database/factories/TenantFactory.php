<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Support\GeoPoint;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->city();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'center_point' => new GeoPoint(
                (float) $this->faker->latitude(43.0, 43.5),
                (float) $this->faker->longitude(-8.5, -8.3),
            ),
            'radius_km' => 10,
            'active' => true,
        ];
    }
}
