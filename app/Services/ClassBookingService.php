<?php

namespace App\Services;

use App\Exceptions\BookingException;
use App\Jobs\NotifyWaitlistPromotion;
use App\Models\ClassBooking;
use App\Models\ClassOccurrence;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

class ClassBookingService
{
    /**
     * Iscrive un membro a un'occorrenza di corso collettivo.
     * Se l'occorrenza è piena, lo mette in waitlist con posizione progressiva.
     *
     * @throws BookingException Se il membro è già iscritto o in waitlist.
     */
    public function enroll(ClassOccurrence $occurrence, Member $member): ClassBooking
    {
        $alreadyEnrolled = ClassBooking::where('class_occurrence_id', $occurrence->id)
            ->where('member_id', $member->id)
            ->whereIn('status', ['confirmed', 'waitlisted'])
            ->exists();

        if ($alreadyEnrolled) {
            throw new BookingException(
                "Il membro è già iscritto o in lista d'attesa per questo corso."
            );
        }

        return DB::transaction(function () use ($occurrence, $member) {
            $fresh = ClassOccurrence::lockForUpdate()->find($occurrence->id);

            if ($fresh->available_spots > 0) {
                return ClassBooking::create([
                    'class_occurrence_id' => $occurrence->id,
                    'member_id'           => $member->id,
                    'status'              => 'confirmed',
                    'position'            => null,
                ]);
            }

            $nextPosition = (int) (ClassBooking::where('class_occurrence_id', $occurrence->id)
                ->where('status', 'waitlisted')
                ->max('position') ?? 0) + 1;

            return ClassBooking::create([
                'class_occurrence_id' => $occurrence->id,
                'member_id'           => $member->id,
                'status'              => 'waitlisted',
                'position'            => $nextPosition,
            ]);
        });
    }

    /**
     * Cancella l'iscrizione di un membro a un corso (cancellata dall'atleta).
     * Se era confermata e l'occorrenza è in futuro, promuove automaticamente
     * il primo in waitlist.
     */
    public function cancel(ClassBooking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $wasConfirmed = $booking->status === 'confirmed';

            $booking->update(['status' => 'cancelled_by_athlete']);

            if ($wasConfirmed) {
                $occurrence = $booking->occurrence;
                $occurrenceStart = \Carbon\Carbon::parse(
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
