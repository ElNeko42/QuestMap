<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Casts\AsGeographyPoint;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'xp',
        'level',
        'last_location',
        'last_location_at',
        'target_quest_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_location' => AsGeographyPoint::class,
            'last_location_at' => 'datetime',
            'xp' => 'integer',
            'level' => 'integer',
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
     * The quest the user flagged as their next destination.
     *
     * @return BelongsTo<Quest, $this>
     */
    public function targetQuest(): BelongsTo
    {
        return $this->belongsTo(Quest::class, 'target_quest_id');
    }

    /**
     * @return HasMany<QuestCompletion, $this>
     */
    public function completions(): HasMany
    {
        return $this->hasMany(QuestCompletion::class);
    }

    /**
     * @return HasMany<UserLocationHistory, $this>
     */
    public function locationHistory(): HasMany
    {
        return $this->hasMany(UserLocationHistory::class);
    }
}
