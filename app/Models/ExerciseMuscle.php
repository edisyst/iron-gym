<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property string $role
 * @property int $contribution_pct
 */
class ExerciseMuscle extends Pivot
{
    protected $table = 'exercise_muscle';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = ['exercise_id', 'muscle_id', 'role', 'contribution_pct'];
}
