<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalRecord extends Model
{
    protected $fillable = [
        'athlete_id',
        'exercise_id',
        'exercise_set_id',
        'record_type',
        'value',
        'achieved_at',
    ];

    protected function casts(): array
    {
        return [
            'achieved_at' => 'datetime',
            'value' => 'float',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }

    /** @return BelongsTo<Exercise, $this> */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    /** @return BelongsTo<ExerciseSet, $this> */
    public function exerciseSet(): BelongsTo
    {
        return $this->belongsTo(ExerciseSet::class);
    }
}
