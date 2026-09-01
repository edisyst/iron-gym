<?php

namespace App\Services;

use App\Jobs\NotifyWaitlistPromotion;
use App\Models\ClassBooking;
use App\Models\ClassOccurrence;
use App\Models\Member;
use App\Models\PtBooking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ClassBookingService
{
    /**
     * Iscrive un membro a un'occorrenza di corso collettivo.
     * Se l'occorrenza è piena, lo mette in waitlist con posizione progressiva.
     *
     * Restituisce EnrollResult: succeeded()=true con il booking, oppure failure enum.
     */
    public function enroll(ClassOccurrence $occurrence, Member $member): EnrollResult
    {
        if ($occurrence->status !== 'planned') {
            return new EnrollResult(booking: null, failure: EnrollFailure::OccurrenceNotEnrollable);
        }

        $occurrenceStart = Carbon::parse(
            $occurrence->date->toDateString().' '.substr($occurrence->start_time, 0, 8)
        );
        $opensAt = $occurrenceStart->copy()->subDays((int) config('classes.booking_opens_days', 7));
        $closesAt = $occurrenceStart->copy()->subMinutes((int) config('classes.booking_closes_minutes', 30));

        if (now()->lt($opensAt)) {
            return new EnrollResult(booking: null, failure: EnrollFailure::NotOpenYet);
        }

        if (now()->gt($closesAt)) {
            return new EnrollResult(booking: null, failure: EnrollFailure::BookingClosed);
        }

        if (! $member->activeSubscription()->exists()) {
            return new EnrollResult(booking: null, failure: EnrollFailure::NoSubscription);
        }

        if (! $member->has_medical_cert_valid) {
            return new EnrollResult(booking: null, failure: EnrollFailure::NoCert);
        }

        $alreadyEnrolled = ClassBooking::where('class_occurrence_id', $occurrence->id)
            ->where('member_id', $member->id)
            ->whereIn('status', ['confirmed', 'waitlisted'])
            ->exists();

        if ($alreadyEnrolled) {
            return new EnrollResult(booking: null, failure: EnrollFailure::AlreadyEnrolled);
        }

        $athleteOverlap = ClassBooking::where('member_id', $member->id)
            ->where('status', 'confirmed')
            ->whereHas('occurrence', function ($q) use ($occurrence) {
                $q->whereDate('date', $occurrence->date->toDateString())
                    ->where('start_time', '<', $occurrence->end_time)
                    ->where('end_time', '>', $occurrence->start_time);
            })
            ->exists();

        if ($athleteOverlap) {
            return new EnrollResult(booking: null, failure: EnrollFailure::AthleteOverlap);
        }

        $ptOverlap = PtBooking::where('member_id', $member->id)
            ->where('status', 'confirmed')
            ->whereDate('booked_date', $occurrence->date->toDateString())
            ->where('start_time', '<', $occurrence->end_time)
            ->where('end_time', '>', $occurrence->start_time)
            ->exists();

        if ($ptOverlap) {
            return new EnrollResult(booking: null, failure: EnrollFailure::PtOverlap);
        }

        $booking = DB::transaction(function () use ($occurrence, $member): ClassBooking {
            $fresh = ClassOccurrence::lockForUpdate()->find($occurrence->id);

            $existing = ClassBooking::where('class_occurrence_id', $occurrence->id)
                ->where('member_id', $member->id)
                ->first();

            if ($fresh->available_spots > 0) {
                if ($existing) {
                    $existing->update(['status' => 'confirmed', 'position' => null, 'booked_by' => null, 'attended_at' => null]);

                    return $existing->fresh();
                }

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

            if ($existing) {
                $existing->update(['status' => 'waitlisted', 'position' => $nextPosition, 'booked_by' => null, 'attended_at' => null]);

                return $existing->fresh();
            }

            return ClassBooking::create([
                'class_occurrence_id' => $occurrence->id,
                'member_id' => $member->id,
                'status' => 'waitlisted',
                'position' => $nextPosition,
            ]);
        });

        return new EnrollResult(booking: $booking, failure: null);
    }

    /**
     * Cancella l'iscrizione di un membro a un corso.
     * $byGym=true: cancellazione da staff (nessun check deadline).
     * $byGym=false (default): cancellazione atleta, verifica deadline free_cancel_hours.
     * Restituisce null in caso di successo, CancelFailure in caso di fallimento.
     * Se era confermata e l'occorrenza è in futuro, promuove automaticamente il primo in waitlist.
     */
    public function cancel(ClassBooking $booking, bool $byGym = false): ?CancelFailure
    {
        if (! in_array($booking->status, ['confirmed', 'waitlisted'], true)) {
            return CancelFailure::NotCancellable;
        }

        if (! $byGym) {
            $occurrence = $booking->occurrence;
            $occurrenceStart = Carbon::parse(
                $occurrence->date->toDateString().' '.substr($occurrence->start_time, 0, 8)
            );
            $freeCancelUntil = $occurrenceStart->copy()->subHours((int) config('classes.free_cancel_hours', 3));

            if (now()->gt($freeCancelUntil)) {
                return CancelFailure::DeadlineExceeded;
            }
        }

        DB::transaction(function () use ($booking, $byGym): void {
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

        return null;
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
