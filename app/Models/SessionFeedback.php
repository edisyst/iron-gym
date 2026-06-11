<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionFeedback extends Model
{
    // Solo created_at: il feedback è immutabile dopo la compilazione
    const UPDATED_AT = null;

    protected $fillable = [
        'session_id',
        'pump',
        'soreness_prev',
        'perceived_effort',
        'joint_pain',
        'performance',
        'sleep_hours',
        'stress_level',
        'note',
    ];

    /**
     * Sessione a cui si riferisce il feedback
     *
     * @return BelongsTo<TrainingSession, self>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'session_id');
    }
}
