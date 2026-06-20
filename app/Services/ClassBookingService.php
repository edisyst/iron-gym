<?php

namespace App\Services;

use App\Exceptions\BookingException;
use App\Jobs\NotifyWaitlistPromotion;
use App\Models\ClassBooking;
use App\Models\GroupClass;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

class ClassBookingService
{
    /**
     * Iscrive un membro a un corso collettivo.
     * Se il corso è pieno, lo mette in waitlist con posizione progressiva.
     *
     * @throws BookingException Se il membro è già iscritto o in waitlist.
     */
    public function enroll(GroupClass $class, Member $member): ClassBooking
    {
        // Verifica che il membro non sia già iscritto o in waitlist
        $alreadyEnrolled = ClassBooking::where('class_id', $class->id)
            ->where('member_id', $member->id)
            ->whereIn('status', ['confirmed', 'waitlisted'])
            ->exists();

        if ($alreadyEnrolled) {
            throw new BookingException(
                "Il membro è già iscritto o in lista d'attesa per questo corso."
            );
        }

        return DB::transaction(function () use ($class, $member) {
            // Rilegge il corso in lock per evitare race condition
            $freshClass = GroupClass::lockForUpdate()->find($class->id);

            if ($freshClass->available_spots > 0) {
                // Posto disponibile: iscrizione diretta confermata
                return ClassBooking::create([
                    'class_id' => $class->id,
                    'member_id' => $member->id,
                    'status' => 'confirmed',
                    'position' => null,
                ]);
            }

            // Corso pieno: inserisce in waitlist con posizione sequenziale
            $nextPosition = (int) (ClassBooking::where('class_id', $class->id)
                ->where('status', 'waitlisted')
                ->max('position') ?? 0) + 1;

            return ClassBooking::create([
                'class_id' => $class->id,
                'member_id' => $member->id,
                'status' => 'waitlisted',
                'position' => $nextPosition,
            ]);
        });
    }

    /**
     * Cancella l'iscrizione di un membro a un corso.
     * Se era confermata e il corso è in futuro, promuove automaticamente
     * il primo in waitlist.
     */
    public function cancel(ClassBooking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $wasConfirmed = $booking->status === 'confirmed';

            $booking->update(['status' => 'cancelled']);

            // Se era confermata e il corso non è ancora iniziato, promuovi il primo in lista
            if ($wasConfirmed && $booking->groupClass->scheduled_at->isFuture()) {
                $this->promoteFirstWaitlisted($booking->groupClass);
            }
        });
    }

    /**
     * Promuove il primo membro in waitlist a confermato e dispatcha la notifica.
     */
    private function promoteFirstWaitlisted(GroupClass $class): void
    {
        $first = ClassBooking::where('class_id', $class->id)
            ->where('status', 'waitlisted')
            ->orderBy('position')
            ->first();

        if ($first === null) {
            return;
        }

        $first->promote();

        // Notifica asincrona dopo che la response HTTP è stata inviata (afterResponse)
        dispatch(new NotifyWaitlistPromotion($first))->afterResponse();
    }
}
