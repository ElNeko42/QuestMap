<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\AsGeographyPoint;
use App\Enums\CompletionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestCompletion extends Model
{
    /** @use HasFactory<\Database\Factories\QuestCompletionFactory> */
    use HasFactory;

    protected $fillable = [
        'quest_id',
        'user_id',
        'submitted_at',
        'location_at_submit',
        'photo_path',
        'data_payload',
        'ai_validation_result',
        'ai_confidence',
        'status',
        'xp_awarded',
    ];

    protected function casts(): array
    {
        return [
            'status' => CompletionStatus::class,
            'location_at_submit' => AsGeographyPoint::class,
            'data_payload' => 'array',
            'ai_validation_result' => 'array',
            'ai_confidence' => 'decimal:2',
            'xp_awarded' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Quest, $this>
     */
    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
