<?php

namespace App\Models;

use Database\Factories\SessionExerciseFeedbackFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionExerciseFeedback extends Model
{
    /** @use HasFactory<SessionExerciseFeedbackFactory> */
    use HasFactory;

    protected $table = 'session_exercise_feedbacks';

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
