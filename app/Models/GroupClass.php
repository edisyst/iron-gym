<?php

namespace App\Models;

use Database\Factories\GroupClassFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupClass extends Model
{
    /** @use HasFactory<GroupClassFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'duration_minutes',
        'default_capacity',
        'room',
        'color',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /** @param Builder<GroupClass> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // -------------------------------------------------------------------------
    // Relazioni
    // -------------------------------------------------------------------------

    /** @return BelongsToMany<User, $this> */
    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_trainer', 'group_class_id', 'trainer_id');
    }

    /** @return HasMany<ClassSchedule, $this> */
    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }

    /** @return HasMany<ClassOccurrence, $this> */
    public function occurrences(): HasMany
    {
        return $this->hasMany(ClassOccurrence::class);
    }
}
