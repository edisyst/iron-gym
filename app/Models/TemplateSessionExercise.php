<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateSessionExercise extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'template_session_id',
        'exercise_id',
        'order_in_session',
        'technique_type',
        'tempo',
        'planned_sets_count',
        'planned_reps',
        'planned_rir',
        'planned_rest_sec',
        'note',
    ];

    /**
     * Sessione del template padre
     *     */
    /** @return BelongsTo<TemplateSession, $this> */
    public function templateSession(): BelongsTo
    {
        return $this->belongsTo(TemplateSession::class);
    }

    /**
     * Esercizio del catalogo
     *     */
    /** @return BelongsTo<Exercise, $this> */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
