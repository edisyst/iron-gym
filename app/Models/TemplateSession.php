<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateSession extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'template_id',
        'week_number',
        'name',
        'order_in_week',
    ];

    /**
     * Template padre
     *     */
    /** @return BelongsTo<WorkoutTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(WorkoutTemplate::class);
    }

    /**
     * Esercizi del template per questa sessione
     *     */
    /** @return HasMany<TemplateSessionExercise, $this> */
    public function templateExercises(): HasMany
    {
        return $this->hasMany(TemplateSessionExercise::class, 'template_session_id');
    }
}
