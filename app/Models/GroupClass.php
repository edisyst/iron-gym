<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\GroupClassFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Carbon $scheduled_at
 * @property int $max_participants
 * @property string $status
 */
class GroupClass extends Model
{
    /** @use HasFactory<GroupClassFactory> */
    use HasFactory;

    protected $fillable = [
        'trainer_id',
        'name',
        'description',
        'scheduled_at',
        'duration_minutes',
        'max_participants',
        'status',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relazioni
    // -------------------------------------------------------------------------

    /** @return BelongsTo<User, $this> */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /** @return HasMany<ClassBooking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(ClassBooking::class, 'class_id');
    }

    /** @return HasMany<ClassBooking, $this> */
    public function confirmedBookings(): HasMany
    {
        return $this->hasMany(ClassBooking::class, 'class_id')
            ->where('status', 'confirmed');
    }

    /** @return HasMany<ClassBooking, $this> */
    public function waitlist(): HasMany
    {
        return $this->hasMany(ClassBooking::class, 'class_id')
            ->where('status', 'waitlisted')
            ->orderBy('position');
    }

    // -------------------------------------------------------------------------
    // Accessor (stile getXAttribute, compatibile Laravel 11 + Livewire 3)
    // -------------------------------------------------------------------------

    /**
     * Numero di partecipanti confermati (N+1 evitato tramite eager load confirmedBookings).
     */
    public function getConfirmedCountAttribute(): int
    {
        // Se la relazione è già caricata usa la collection, altrimenti fa la query
        if ($this->relationLoaded('confirmedBookings')) {
            return $this->confirmedBookings->count();
        }

        return $this->confirmedBookings()->count();
    }

    /**
     * Posti disponibili rimanenti (minimo 0).
     */
    public function getAvailableSpotsAttribute(): int
    {
        return max(0, $this->max_participants - $this->confirmed_count);
    }

    /**
     * Indica se il corso è al completo.
     */
    public function getIsFullAttribute(): bool
    {
        return $this->available_spots === 0;
    }
}
