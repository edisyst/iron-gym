<?php

namespace App\Services;

use App\Exceptions\BookingException;
use App\Models\PtBooking;
use App\Models\TrainerAvailability;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PtBookingService
{
    /**
     * Prenota una sessione PT per un membro con un trainer.
     *
     * @param  string  $startTime  Formato HH:MM
     * @param  string  $endTime  Formato HH:MM
     *
     * @throws BookingException Se lo slot è già occupato o il trainer non ha disponibilità.
     */
    public function book(
        int $trainerId,
        int $memberId,
        Carbon $date,
        string $startTime,
        string $endTime,
    ): PtBooking {
        // Calcola durata in minuti per il controllo disponibilità
        $startCarbon = Carbon::createFromFormat('H:i', $startTime);
        $endCarbon = Carbon::createFromFormat('H:i', $endTime);
        $durationMinutes = (int) $startCarbon->diffInMinutes($endCarbon);

        return DB::transaction(function () use (
            $trainerId, $memberId, $date, $startTime, $endTime, $durationMinutes
        ) {
            // 1. Verifica sovrapposizione con prenotazioni PT esistenti
            $overlaps = PtBooking::where('trainer_id', $trainerId)
                ->where('booked_date', $date->toDateString())
                ->whereIn('status', ['pending', 'confirmed'])
                ->where(function ($q) use ($startTime, $endTime) {
                    // Due intervalli si sovrappongono se: start1 < end2 AND end1 > start2
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                })
                ->exists();

            if ($overlaps) {
                throw new BookingException(
                    "Lo slot {$startTime}-{$endTime} del {$date->toDateString()} è già occupato."
                );
            }

            // 2. Verifica che il trainer abbia disponibilità per questo slot
            $availableSlots = TrainerAvailability::getAvailableSlots($trainerId, $date, $durationMinutes);

            $slotExists = $availableSlots->first(function (array $slot) use ($startTime) {
                return $slot['start'] === $startTime;
            });

            if ($slotExists === null) {
                throw new BookingException(
                    "Il trainer non ha disponibilità per lo slot {$startTime}-{$endTime} del {$date->toDateString()}."
                );
            }

            // 3. Calcola deadline gratuita di cancellazione: 24 ore prima
            $cancellationDeadline = Carbon::parse(
                $date->toDateString().' '.$startTime
            )->subHours(24);

            // 4. Crea e restituisce la prenotazione confermata
            return PtBooking::create([
                'trainer_id' => $trainerId,
                'member_id' => $memberId,
                'booked_date' => $date->toDateString(),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => 'confirmed',
                'cancellation_deadline' => $cancellationDeadline,
            ]);
        });
    }

    /**
     * Annulla una prenotazione PT.
     *
     * @throws BookingException Se la prenotazione non è in uno stato cancellabile.
     */
    public function cancel(PtBooking $booking, User $cancelledBy, string $reason = ''): void
    {
        // Solo pending e confirmed sono cancellabili
        if (! in_array($booking->status, ['pending', 'confirmed'], strict: true)) {
            throw new BookingException(
                "Prenotazione non cancellabile (status: {$booking->status})."
            );
        }

        $booking->update([
            'status' => 'cancelled',
            'cancelled_by' => $cancelledBy->id,
            'cancellation_reason' => $reason,
        ]);
    }
}
