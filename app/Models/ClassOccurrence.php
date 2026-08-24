<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\ClassOccurrenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Carbon $date
 * @property int $capacity
 * @property string $status
 */
class ClassOccurrence extends Model
{
    /** @use HasFactory<ClassOccurrenceFactory> */
    use HasFactory;

    protected $fillable = [
        'group_class_id',
        'class_schedule_id',
        'date',
        'start_time',
        'end_time',
        'trainer_id',
        'capacity',
        'status',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
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

    /** @return BelongsTo<ClassSchedule, $this> */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class, 'class_schedule_id');
    }

    /** @return BelongsTo<User, $this> */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /** @return HasMany<ClassBooking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(ClassBooking::class);
    }

    /** @return HasMany<ClassBooking, $this> */
    public function confirmedBookings(): HasMany
    {
        return $this->hasMany(ClassBooking::class)
            ->where('status', 'confirmed');
    }

    /** @return HasMany<ClassBooking, $this> */
    public function waitlist(): HasMany
    {
        return $this->hasMany(ClassBooking::class)
            ->where('status', 'waitlisted')
            ->orderBy('position');
    }

    // -------------------------------------------------------------------------
    // Accessor (stile getXAttribute, compatibile Laravel 11 + Livewire 3)
    // -------------------------------------------------------------------------

    public function getConfirmedCountAttribute(): int
    {
        if ($this->relationLoaded('confirmedBookings')) {
            return $this->confirmedBookings->count();
        }

        return $this->confirmedBookings()->count();
    }

    public function getAvailableSpotsAttribute(): int
    {
        return max(0, $this->capacity - $this->confirmed_count);
    }

    public function getIsFullAttribute(): bool
    {
        return $this->available_spots === 0;
    }
}
