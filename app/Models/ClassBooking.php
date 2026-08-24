<?php

namespace App\Models;

use Database\Factories\ClassBookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassBooking extends Model
{
    /** @use HasFactory<ClassBookingFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'class_occurrence_id',
        'member_id',
        'status',
        'position',
        'attended_at',
        'booked_by',
    ];

    protected function casts(): array
    {
        return [
            'attended_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relazioni
    // -------------------------------------------------------------------------

    /** @return BelongsTo<ClassOccurrence, $this> */
    public function occurrence(): BelongsTo
    {
        return $this->belongsTo(ClassOccurrence::class, 'class_occurrence_id');
    }

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** @return BelongsTo<User, $this> */
    public function bookedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by');
    }

    // -------------------------------------------------------------------------
    // Azioni di dominio
    // -------------------------------------------------------------------------

    /**
     * Promuove questa iscrizione da waitlist a confirmed.
     * Scala di una posizione tutte le iscrizioni in waitlist successive.
     */
    public function promote(): void
    {
        $oldPosition = $this->position;

        $this->update([
            'status'   => 'confirmed',
            'position' => null,
        ]);

        ClassBooking::where('class_occurrence_id', $this->class_occurrence_id)
            ->where('status', 'waitlisted')
            ->where('position', '>', $oldPosition)
            ->decrement('position');
    }
}
