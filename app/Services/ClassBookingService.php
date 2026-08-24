<?php

namespace App\Services;

use App\Exceptions\BookingException;
use App\Jobs\NotifyWaitlistPromotion;
use App\Models\ClassBooking;
use App\Models\ClassOccurrence;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ClassBookingService
{
    /**
     * Iscrive un membro a un'occorrenza di corso collettivo.
     * Se l'occorrenza è piena, lo mette in waitlist con posizione progressiva.
     *
     * @throws BookingException Se prerequisiti non soddisfatti, già iscritto o orario sovrapposto.
     */
    public function enroll(ClassOccurrence $occurrence, Member $member): ClassBooking
    {
        // Prerequisito 1: abbonamento attivo
        if (! $member->activeSubscription()->exists()) {
            throw new BookingException('Nessun abbonamento attivo.');
        }

        // Prerequisito 2: certificato medico valido
        if (! $member->has_medical_cert_valid) {
            throw new BookingException('Certificato medico scaduto o assente.');
        }

        $alreadyEnrolled = ClassBooking::where('class_occurrence_id', $occurrence->id)
            ->where('member_id', $member->id)
            ->whereIn('status', ['confirmed', 'waitlisted'])
            ->exists();

        if ($alreadyEnrolled) {
            throw new BookingException(
                "Il membro è già iscritto o in lista d'attesa per questo corso."
            );
        }

        // Overlap: membro già confermato in un corso sovrapposto lo stesso giorno
        $athleteOverlap = ClassBooking::where('member_id', $member->id)
            ->where('status', 'confirmed')
            ->whereHas('occurrence', function ($q) use ($occurrence) {
                $q->whereDate('date', $occurrence->date->toDateString())
                    ->where('start_time', '<', $occurrence->end_time)
                    ->where('end_time', '>', $occurrence->start_time);
            })
            ->exists();

        if ($athleteOverlap) {
            throw new BookingException('Hai già un corso confermato in questo orario.');
        }

        return DB::transaction(function () use ($occurrence, $member) {
            $fresh = ClassOccurrence::lockForUpdate()->find($occurrence->id);

            if ($fresh->available_spots > 0) {
                return ClassBooking::create([
                    'class_occurrence_id' => $occurrence->id,
                    'member_id' => $member->id,
                    'status' => 'confirmed',
                    'position' => null,
                ]);
            }

            $nextPosition = (int) (ClassBooking::where('class_occurrence_id', $occurrence->id)
                ->where('status', 'waitlisted')
                ->max('position') ?? 0) + 1;

            return ClassBooking::create([
                'class_occurrence_id' => $occurrence->id,
                'member_id' => $member->id,
                'status' => 'waitlisted',
                'position' => $nextPosition,
            ]);
        });
    }

    /**
     * Cancella l'iscrizione di un membro a un corso.
     * $byGym=true: cancellazione da staff (status = cancelled_by_gym, nessuna restrizione di finestra).
     * $byGym=false (default): cancellazione atleta (status = cancelled_by_athlete).
     * La verifica della finestra di cancellazione gratuita è responsabilità del chiamante (Livewire).
     * Se era confermata e l'occorrenza è in futuro, promuove automaticamente il primo in waitlist.
     */
    public function cancel(ClassBooking $booking, bool $byGym = false): void
    {
        DB::transaction(function () use ($booking, $byGym) {
            $wasConfirmed = $booking->status === 'confirmed';

            $booking->update(['status' => $byGym ? 'cancelled_by_gym' : 'cancelled_by_athlete']);

            if ($wasConfirmed) {
                $occurrence = $booking->occurrence;
                $occurrenceStart = Carbon::parse(
                    $occurrence->date->toDateString().' '.substr($occurrence->start_time, 0, 8)
                );

                if ($occurrenceStart->isFuture()) {
                    $this->promoteFirstWaitlisted($occurrence);
                }
            }
        });
    }

    private function promoteFirstWaitlisted(ClassOccurrence $occurrence): void
    {
        $first = ClassBooking::where('class_occurrence_id', $occurrence->id)
            ->where('status', 'waitlisted')
            ->orderBy('position')
            ->first();

        if ($first === null) {
            return;
        }

        $first->promote();

        dispatch(new NotifyWaitlistPromotion($first))->afterResponse();
    }
}
