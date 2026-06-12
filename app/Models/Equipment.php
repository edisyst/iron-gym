<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Equipment extends Model
{
    public $timestamps = false;

    protected $fillable = ['slug', 'name_it'];

    /**
     * Esercizi che richiedono questo equipment
     *     */
    /** @return BelongsToMany<Exercise, $this> */
    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class, 'exercise_equipment');
    }
}
