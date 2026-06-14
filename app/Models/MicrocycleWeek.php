<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\MicrocycleWeekFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Carbon $start_date
 * @property Carbon $end_date
 */
class MicrocycleWeek extends Model
{
    /** @use HasFactory<MicrocycleWeekFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'mesocycle_id',
        'week_number',
        'is_deload',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'is_deload' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * Mesociclo padre
     *     */
    /** @return BelongsTo<Mesocycle, $this> */
    public function mesocycle(): BelongsTo
    {
        return $this->belongsTo(Mesocycle::class);
    }

    /**
     * Sessioni di questa settimana
     *     */
    /** @return HasMany<TrainingSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class, 'microcycle_week_id');
    }
}
