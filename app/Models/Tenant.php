<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\AsGeographyPoint;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    /** @use HasFactory<\Database\Factories\TenantFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'center_point',
        'radius_km',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'center_point' => AsGeographyPoint::class,
            'radius_km' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Quest, $this>
     */
    public function quests(): HasMany
    {
        return $this->hasMany(Quest::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
