<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassBooking extends Model
{
    // Nessun updated_at: le prenotazioni corso non vengono aggiornate parzialmente
    public const UPDATED_AT = null;

    protected $fillable = [
        'class_id',
        'member_id',
        'status',
        'position',
    ];

    // -------------------------------------------------------------------------
    // Relazioni
    // -------------------------------------------------------------------------

    /** @return BelongsTo<GroupClass, $this> */
    public function groupClass(): BelongsTo
    {
        return $this->belongsTo(GroupClass::class, 'class_id');
    }

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
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
        // Salva la posizione corrente prima di azzerarla
        $oldPosition = $this->position;

        // Promuove l'iscrizione a confermata
        $this->update([
            'status' => 'confirmed',
            'position' => null,
        ]);

        // Scala le posizioni successive nella waitlist
        ClassBooking::where('class_id', $this->class_id)
            ->where('status', 'waitlisted')
            ->where('position', '>', $oldPosition)
            ->decrement('position');
    }
}
