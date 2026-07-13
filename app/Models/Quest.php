<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\AsGeographyPoint;
use App\Enums\CreatorType;
use App\Enums\QuestStatus;
use App\Enums\ValidationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quest extends Model
{
    /** @use HasFactory<\Database\Factories\QuestFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'creator_type',
        'title',
        'description',
        'category',
        'location',
        'geofence_radius_m',
        'validation_type',
        'validation_prompt',
        'xp_reward',
        'starts_at',
        'expires_at',
        'max_completions',
        'completions_count',
        'dedup_hash',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'creator_type' => CreatorType::class,
            'validation_type' => ValidationType::class,
            'status' => QuestStatus::class,
            'location' => AsGeographyPoint::class,
            'geofence_radius_m' => 'integer',
            'xp_reward' => 'integer',
            'max_completions' => 'integer',
            'completions_count' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
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
     * @return HasMany<QuestCompletion, $this>
     */
    public function completions(): HasMany
    {
        return $this->hasMany(QuestCompletion::class);
    }

    /**
     * Active and currently within its start/expiry window.
     *
     * @param  Builder<Quest>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $now = now();

        $query->where('status', QuestStatus::Active->value)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', $now));
    }

    public function isOpenForCompletion(): bool
    {
        if ($this->status !== QuestStatus::Active) {
            return false;
        }

        if ($this->max_completions !== null && $this->completions_count >= $this->max_completions) {
            return false;
        }

        $now = now();

        if ($this->starts_at !== null && $this->starts_at->isAfter($now)) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isBefore($now)) {
            return false;
        }

        return true;
    }
}
