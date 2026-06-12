<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionExerciseFeedback extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_exercise_id',
        'joint_pain',
        'pump',
        'note',
    ];

    /**
     * SessionExercise a cui si riferisce il feedback mirato
     *     */
    /** @return BelongsTo<SessionExercise, $this> */
    public function sessionExercise(): BelongsTo
    {
        return $this->belongsTo(SessionExercise::class);
    }
}
