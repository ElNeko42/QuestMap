<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\QuestCompletion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin QuestCompletion
 */
class CompletionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quest_id' => $this->quest_id,
            'user_id' => $this->user_id,
            'status' => $this->status->value,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'location_at_submit' => $this->location_at_submit?->toArray(),
            'ai_confidence' => $this->ai_confidence !== null ? (float) $this->ai_confidence : null,
            'xp_awarded' => (int) $this->xp_awarded,
            'data_payload' => $this->data_payload,
            'quest' => new QuestResource($this->whenLoaded('quest')),
        ];
    }
}
