<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\TrainingSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 */
class TrainingSession extends Model
{
    /** @use HasFactory<TrainingSessionFactory> */
    use HasFactory;

    // Usa training_sessions per non collidere con la tabella sessions delle HTTP session di Laravel
    protected $table = 'training_sessions';

    protected $fillable = [
        'microcycle_week_id',
        'name',
        'order_in_week',
        'scheduled_date',
        'started_at',
        'completed_at',
        'status',
        'athlete_notes',
        'trainer_notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Settimana del mesociclo a cui appartiene la sessione
     *     */
    /** @return BelongsTo<MicrocycleWeek, $this> */
    public function week(): BelongsTo
    {
        return $this->belongsTo(MicrocycleWeek::class, 'microcycle_week_id');
    }

    /**
     * Gruppi di esercizi (superset, giant set, circuit) della sessione
     *     */
    /** @return HasMany<SessionExerciseGroup, $this> */
    public function exerciseGroups(): HasMany
    {
        return $this->hasMany(SessionExerciseGroup::class, 'session_id');
    }

    /**
     * Esercizi della sessione (inclusi quelli dentro gruppi)
     *     */
    /** @return HasMany<SessionExercise, $this> */
    public function sessionExercises(): HasMany
    {
        return $this->hasMany(SessionExercise::class, 'session_id');
    }

    /**
     * Feedback post-sessione compilato dall'atleta
     *     */
    /** @return HasOne<SessionFeedback, $this> */
    public function feedback(): HasOne
    {
        return $this->hasOne(SessionFeedback::class, 'session_id');
    }

    /**
     * Check di readiness compilato prima dell'avvio della sessione
     *     */
    /** @return HasOne<SessionReadinessCheck, $this> */
    public function readinessCheck(): HasOne
    {
        return $this->hasOne(SessionReadinessCheck::class, 'training_session_id');
    }
}
