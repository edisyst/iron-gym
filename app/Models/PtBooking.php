<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\PtBookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Carbon $booked_date
 * @property Carbon|null $cancellation_deadline
 * @property string $status
 */
class PtBooking extends Model
{
    /** @use HasFactory<PtBookingFactory> */
    use HasFactory;

    protected $fillable = [
        'trainer_id',
        'member_id',
        'session_id',
        'booked_date',
        'start_time',
        'end_time',
        'status',
        'cancelled_by',
        'cancellation_reason',
        'cancellation_deadline',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'booked_date' => 'date',
            'cancellation_deadline' => 'datetime',
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

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** @return BelongsTo<TrainingSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class);
    }

    /** @return BelongsTo<User, $this> */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Indica se la cancellazione è ancora gratuita (entro la deadline).
     */
    public function canBeCancelledFree(): bool
    {
        if ($this->cancellation_deadline === null) {
            return true;
        }

        return now()->lt($this->cancellation_deadline);
    }
}
