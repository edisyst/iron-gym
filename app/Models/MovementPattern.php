<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MovementPattern extends Model
{
    protected $table = 'movement_patterns';

    public $timestamps = false;

    protected $fillable = ['slug', 'name_it', 'category', 'display_order'];

    /**
     * Scope: solo pattern di categoria compound_pattern
     *
     * @param  Builder<MovementPattern>  $query
     * @return Builder<MovementPattern>
     */
    public function scopeCompoundPatterns(Builder $query): Builder
    {
        return $query->where('category', 'compound_pattern');
    }

    /**
     * Scope: solo pattern di categoria joint_action
     *
     * @param  Builder<MovementPattern>  $query
     * @return Builder<MovementPattern>
     */
    public function scopeJointActions(Builder $query): Builder
    {
        return $query->where('category', 'joint_action');
    }

    /**
     * Esercizi che usano questo pattern come compound_pattern
     *
     * @return HasMany<Exercise, self>
     */
    public function exercisesAsCompound(): HasMany
    {
        return $this->hasMany(Exercise::class, 'compound_pattern_id');
    }

    /**
     * Esercizi che usano questo pattern come joint_action
     *
     * @return HasMany<Exercise, self>
     */
    public function exercisesAsJointAction(): HasMany
    {
        return $this->hasMany(Exercise::class, 'joint_action_id');
    }
}
