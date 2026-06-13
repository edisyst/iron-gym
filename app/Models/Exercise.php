<?php

namespace App\Models;

use Database\Factories\ExerciseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exercise extends Model
{
    /** @use HasFactory<ExerciseFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'slug',
        'name_it',
        'description',
        'compound_pattern_id',
        'joint_action_id',
        'mechanic',
        'plane',
        'laterality',
        'skill_level',
        'measurement_type',
        'video_url',
        'thumbnail_url',
        'created_by',
    ];

    /**
     * Pattern motorio compound (XOR con joint_action_id)
     *     */
    /** @return BelongsTo<MovementPattern, $this> */
    public function compoundPattern(): BelongsTo
    {
        return $this->belongsTo(MovementPattern::class, 'compound_pattern_id');
    }

    /**
     * Azione articolare (XOR con compound_pattern_id)
     *     */
    /** @return BelongsTo<MovementPattern, $this> */
    public function jointAction(): BelongsTo
    {
        return $this->belongsTo(MovementPattern::class, 'joint_action_id');
    }

    /**
     * Muscoli coinvolti nell'esercizio (con ruolo e contribution_pct)
     *     */
    /** @return BelongsToMany<Muscle, $this, ExerciseMuscle> */
    public function muscles(): BelongsToMany
    {
        return $this->belongsToMany(Muscle::class, 'exercise_muscle')
            ->withPivot('role', 'contribution_pct')
            ->using(ExerciseMuscle::class);
    }

    /**
     * Attrezzatura richiesta per l'esercizio
     *     */
    /** @return BelongsToMany<Equipment, $this> */
    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class, 'exercise_equipment');
    }

    /**
     * Trainer che ha creato l'esercizio nel catalogo
     *     */
    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
