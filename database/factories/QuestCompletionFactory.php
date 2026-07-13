<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CompletionStatus;
use App\Models\Quest;
use App\Models\User;
use App\Support\GeoPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\QuestCompletion>
 */
class QuestCompletionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quest_id' => Quest::factory(),
            'user_id' => User::factory(),
            'submitted_at' => now(),
            'location_at_submit' => new GeoPoint(43.3623, -8.4115),
            'photo_path' => null,
            'data_payload' => null,
            'ai_validation_result' => null,
            'ai_confidence' => null,
            'status' => CompletionStatus::Pending,
            'xp_awarded' => 0,
        ];
    }

    public function approved(int $xp = 100): static
    {
        return $this->state(fn () => [
            'status' => CompletionStatus::Approved,
            'ai_confidence' => 0.95,
            'xp_awarded' => $xp,
        ]);
    }
}
