<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionExerciseGroup extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'group_type',
        'order_in_session',
        'rounds',
        'rest_between_rounds_sec',
    ];

    /**
     * Sessione a cui appartiene il gruppo
     *
     * @return BelongsTo<TrainingSession, self>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'session_id');
    }

    /**
     * Esercizi del gruppo (superset/giant set/circuit)
     *
     * @return HasMany<SessionExercise, self>
     */
    public function sessionExercises(): HasMany
    {
        return $this->hasMany(SessionExercise::class, 'group_id');
    }
}
