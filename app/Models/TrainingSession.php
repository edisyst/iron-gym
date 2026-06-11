<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TrainingSession extends Model
{
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
            'started_at'     => 'datetime',
            'completed_at'   => 'datetime',
        ];
    }

    /**
     * Settimana del mesociclo a cui appartiene la sessione
     *
     * @return BelongsTo<MicrocycleWeek, self>
     */
    public function week(): BelongsTo
    {
        return $this->belongsTo(MicrocycleWeek::class, 'microcycle_week_id');
    }

    /**
     * Gruppi di esercizi (superset, giant set, circuit) della sessione
     *
     * @return HasMany<SessionExerciseGroup, self>
     */
    public function exerciseGroups(): HasMany
    {
        return $this->hasMany(SessionExerciseGroup::class, 'session_id');
    }

    /**
     * Esercizi della sessione (inclusi quelli dentro gruppi)
     *
     * @return HasMany<SessionExercise, self>
     */
    public function sessionExercises(): HasMany
    {
        return $this->hasMany(SessionExercise::class, 'session_id');
    }

    /**
     * Feedback post-sessione compilato dall'atleta
     *
     * @return HasOne<SessionFeedback, self>
     */
    public function feedback(): HasOne
    {
        return $this->hasOne(SessionFeedback::class, 'session_id');
    }
}
