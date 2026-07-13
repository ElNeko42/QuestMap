<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\AsGeographyPoint;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLocationHistory extends Model
{
    /** @use HasFactory<\Database\Factories\UserLocationHistoryFactory> */
    use HasFactory;

    protected $table = 'user_location_history';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'location',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'location' => AsGeographyPoint::class,
            'recorded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
