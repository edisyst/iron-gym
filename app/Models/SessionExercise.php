<?php

namespace App\Models;

use Database\Factories\SessionExerciseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SessionExercise extends Model
{
    /** @use HasFactory<SessionExerciseFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'group_id',
        'exercise_id',
        'order_in_session',
        'order_in_group',
        'technique_type',
        'tempo',
        'planned_sets_count',
        'planned_rest_sec',
        'intra_cluster_rest_sec',
        'trainer_note',
    ];

    /**
     * Sessione padre
     *     */
    /** @return BelongsTo<TrainingSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'session_id');
    }

    /**
     * Gruppo di appartenenza (NULL se straight set isolato)
     *     */
    /** @return BelongsTo<SessionExerciseGroup, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(SessionExerciseGroup::class, 'group_id');
    }

    /**
     * Esercizio del catalogo
     *     */
    /** @return BelongsTo<Exercise, $this> */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    /**
     * Set dell'esercizio in questa sessione
     *     */
    /** @return HasMany<ExerciseSet, $this> */
    public function sets(): HasMany
    {
        return $this->hasMany(ExerciseSet::class, 'session_exercise_id');
    }

    /**
     * Feedback specifico per questo esercizio (joint pain mirato)
     *     */
    /** @return HasOne<SessionExerciseFeedback, $this> */
    public function feedback(): HasOne
    {
        return $this->hasOne(SessionExerciseFeedback::class, 'session_exercise_id');
    }
}
