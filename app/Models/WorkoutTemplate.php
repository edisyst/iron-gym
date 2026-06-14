<?php

namespace App\Models;

use Database\Factories\WorkoutTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkoutTemplate extends Model
{
    /** @use HasFactory<WorkoutTemplateFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'goal',
        'periodization_model',
        'weeks_count',
        'days_per_week',
        'created_by',
        'is_active',
    ];

    /**
     * Cast di is_active a booleano per convenienza
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Trainer che ha creato il template
     *     */
    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Sessioni del template (struttura scheda)
     *     */
    /** @return HasMany<TemplateSession, $this> */
    public function templateSessions(): HasMany
    {
        return $this->hasMany(TemplateSession::class, 'template_id');
    }

    /**
     * Mesocicli istanziati da questo template
     *     */
    /** @return HasMany<Mesocycle, $this> */
    public function mesocycles(): HasMany
    {
        return $this->hasMany(Mesocycle::class, 'template_id');
    }
}
