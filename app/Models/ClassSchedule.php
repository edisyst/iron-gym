<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\ClassScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_until
 */
class ClassSchedule extends Model
{
    /** @use HasFactory<ClassScheduleFactory> */
    use HasFactory;

    protected $fillable = [
        'group_class_id',
        'weekday',
        'start_time',
        'trainer_id',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Relazioni
    // -------------------------------------------------------------------------

    /** @return BelongsTo<GroupClass, $this> */
    public function groupClass(): BelongsTo
    {
        return $this->belongsTo(GroupClass::class);
    }

    /** @return BelongsTo<User, $this> */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /** @return HasMany<ClassOccurrence, $this> */
    public function occurrences(): HasMany
    {
        return $this->hasMany(ClassOccurrence::class);
    }
}
