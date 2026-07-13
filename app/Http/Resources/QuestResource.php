<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Quest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Quest
 */
class QuestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'creator_type' => $this->creator_type->value,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'location' => $this->location?->toArray(),
            'geofence_radius_m' => (int) $this->geofence_radius_m,
            'validation_type' => $this->validation_type->value,
            'xp_reward' => (int) $this->xp_reward,
            'status' => $this->status->value,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'max_completions' => $this->max_completions,
            'completions_count' => (int) $this->completions_count,
            // Present only on nearby results (computed by ST_Distance).
            'dist_m' => $this->when(
                $this->resource->getAttribute('dist_m') !== null,
                fn () => round((float) $this->resource->getAttribute('dist_m'), 1),
            ),
        ];
    }
}
