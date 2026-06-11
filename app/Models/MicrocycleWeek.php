<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MicrocycleWeek extends Model
{
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
            'is_deload'  => 'boolean',
            'start_date' => 'date',
            'end_date'   => 'date',
        ];
    }

    /**
     * Mesociclo padre
     *
     * @return BelongsTo<Mesocycle, self>
     */
    public function mesocycle(): BelongsTo
    {
        return $this->belongsTo(Mesocycle::class);
    }

    /**
     * Sessioni di questa settimana
     *
     * @return HasMany<TrainingSession, self>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class, 'microcycle_week_id');
    }
}
