<?php

namespace App\Models;

use Database\Factories\ExerciseSetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExerciseSet extends Model
{
    /** @use HasFactory<ExerciseSetFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'session_exercise_id',
        'set_index',
        'set_sequence_id',
        'sequence_index',
        'set_subtype',
        'is_warmup',
        'planned_reps',
        'planned_weight_kg',
        'planned_rir',
        'planned_rpe',
        'planned_duration_sec',
        'actual_reps',
        'actual_weight_kg',
        'actual_rir',
        'actual_rpe',
        'actual_duration_sec',
        'completed_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'is_warmup' => 'boolean',
        ];
    }

    /**
     * SessionExercise a cui appartiene il set
     *     */
    /** @return BelongsTo<SessionExercise, $this> */
    public function sessionExercise(): BelongsTo
    {
        return $this->belongsTo(SessionExercise::class);
    }

    /**
     * Estimated 1RM calcolato con formula Epley: w * (1 + r/30)
     * Restituisce NULL se actual_weight_kg o actual_reps non sono valorizzati
     */
    public function getEstimated1rmAttribute(): ?float
    {
        if ($this->actual_weight_kg === null || $this->actual_reps === null || $this->actual_reps == 0) {
            return null;
        }

        return round($this->actual_weight_kg * (1 + $this->actual_reps / 30), 2);
    }
}
