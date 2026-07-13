<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Route extends Model
{
    /** @use HasFactory<\Database\Factories\RouteFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'title',
        'theme',
        'description',
        'quest_ids',
        'estimated_minutes',
    ];

    protected function casts(): array
    {
        return [
            'quest_ids' => 'array',
            'estimated_minutes' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Load the quests referenced by quest_ids, preserving their order.
     *
     * @return \Illuminate\Support\Collection<int, Quest>
     */
    public function quests(): \Illuminate\Support\Collection
    {
        $ids = $this->quest_ids ?? [];

        if ($ids === []) {
            return collect();
        }

        $quests = Quest::whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)
            ->map(fn ($id) => $quests->get($id))
            ->filter()
            ->values();
    }
}
