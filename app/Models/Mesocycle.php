<?php

namespace App\Models;

use Database\Factories\MesocycleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mesocycle extends Model
{
    /** @use HasFactory<MesocycleFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'athlete_id',
        'trainer_id',
        'template_id',
        'name',
        'goal',
        'periodization_model',
        'start_date',
        'weeks_count',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
        ];
    }

    /**
     * Atleta a cui è assegnato il mesociclo
     *     */
    /** @return BelongsTo<User, $this> */
    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }

    /**
     * Trainer che ha creato/assegnato il mesociclo
     *     */
    /** @return BelongsTo<User, $this> */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /**
     * Template da cui è stato istanziato (nullable: può essere creato da zero)
     *     */
    /** @return BelongsTo<WorkoutTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(WorkoutTemplate::class);
    }

    /**
     * Settimane del mesociclo
     *     */
    /** @return HasMany<MicrocycleWeek, $this> */
    public function weeks(): HasMany
    {
        return $this->hasMany(MicrocycleWeek::class);
    }

    /**
     * Tutte le sessioni del mesociclo (through microcycle_weeks)
     *     */
    /** @return HasManyThrough<TrainingSession, MicrocycleWeek, $this> */
    public function sessions(): HasManyThrough
    {
        return $this->hasManyThrough(TrainingSession::class, MicrocycleWeek::class);
    }
}
