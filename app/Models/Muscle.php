<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Muscle extends Model
{
    public $timestamps = false;

    protected $fillable = ['slug', 'name_it', 'muscle_group', 'muscle_head', 'display_order'];

    /**
     * Esercizi che coinvolgono questo muscolo (con ruolo e contribution_pct)
     *
     * @return BelongsToMany<Exercise, self>
     */
    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class, 'exercise_muscle')
            ->withPivot('role', 'contribution_pct')
            ->using(ExerciseMuscle::class);
    }

    /**
     * Volume landmarks degli atleti per questo muscolo
     *
     * @return HasMany<AthleteVolumeLandmark, self>
     */
    public function volumeLandmarks(): HasMany
    {
        return $this->hasMany(AthleteVolumeLandmark::class);
    }
}
