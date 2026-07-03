<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionReadinessCheck extends Model
{
    protected $table = 'session_readiness_checks';

    const UPDATED_AT = null;

    protected $fillable = [
        'training_session_id',
        'sleep_quality',
        'stress_level',
        'soreness_level',
        'joint_status',
        'note',
    ];

    /**
     * Sessione a cui si riferisce il check pre-allenamento
     *
     * @return BelongsTo<TrainingSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
    }

    /**
     * Score aggregato 0-12: somma dei quattro campi (0 pessimo, 3 ottimo per campo)
     */
    public function getScoreAttribute(): int
    {
        return $this->sleep_quality + $this->stress_level + $this->soreness_level + $this->joint_status;
    }
}
