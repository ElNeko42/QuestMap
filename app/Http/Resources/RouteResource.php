<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Route
 */
class RouteResource extends JsonResource
{
    /**
     * When true, embed the ordered quests (used on the detail endpoint).
     */
    public bool $withQuests = false;

    public function withQuests(bool $value = true): static
    {
        $this->withQuests = $value;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'title' => $this->title,
            'theme' => $this->theme,
            'description' => $this->description,
            'quest_ids' => $this->quest_ids,
            'estimated_minutes' => (int) $this->estimated_minutes,
            'quests' => $this->when(
                $this->withQuests,
                fn () => QuestResource::collection($this->quests()),
            ),
        ];
    }
}
