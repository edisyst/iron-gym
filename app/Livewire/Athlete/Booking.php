<?php

namespace App\Livewire\Athlete;

use App\Exceptions\BookingException;
use App\Models\ClassBooking;
use App\Models\ClassOccurrence;
use App\Models\Member;
use App\Models\Mesocycle;
use App\Models\PtBooking;
use App\Models\TrainerAvailability;
use App\Models\User;
use App\Services\ClassBookingService;
use App\Services\PtBookingService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Laravel\Pennant\Feature;
use Livewire\Component;

class Booking extends Component
{
    public string $activeTab = 'pt'; // 'pt' | 'classes'

    public int $selectedTrainerId = 0;

    public string $selectedDate = '';

    /** @var Collection<int, array{start: string, end: string}> */
    public Collection $availableSlots;

    public string $selectedStart = '';

    public string $selectedEnd = '';

    public int $enrollErrorId = 0;

    public string $enrollErrorMsg = '';

    public function mount(): void
    {
        $this->availableSlots = collect();
        $this->selectedDate = now()->toDateString();
    }

    // -------------------------------------------------------------------------
    // Livewire watchers
    // -------------------------------------------------------------------------

    public function updatedSelectedDate(): void
    {
        if ($this->selectedTrainerId > 0) {
            $this->loadAvailableSlots();
        }

        $this->selectedStart = '';
        $this->selectedEnd = '';
    }

    public function updatedSelectedTrainerId(): void
    {
        $this->selectedStart = '';
        $this->selectedEnd = '';
        $this->loadAvailableSlots();
    }

    // -------------------------------------------------------------------------
    // Slot disponibili PT
    // -------------------------------------------------------------------------

    public function loadAvailableSlots(): void
    {
        if ($this->selectedTrainerId === 0 || $this->selectedDate === '') {
            $this->availableSlots = collect();

            return;
        }

        $this->availableSlots = TrainerAvailability::getAvailableSlots(
            trainerId: $this->selectedTrainerId,
            date: Carbon::parse($this->selectedDate),
            durationMinutes: 60,
        );
    }

    public function selectSlot(string $start, string $end): void
    {
        $this->selectedStart = $start;
        $this->selectedEnd = $end;
    }

    // -------------------------------------------------------------------------
    // Prenotazioni PT
    // -------------------------------------------------------------------------

    public function bookPt(): void
    {
        abort_unless(Feature::active('pt_bookings'), 403);

        $this->validate([
            'selectedTrainerId' => 'required|integer|min:1',
            'selectedDate' => 'required|date|after_or_equal:today',
            'selectedStart' => 'required',
            'selectedEnd' => 'required',
        ], [
            'selectedTrainerId.min' => 'Seleziona un trainer.',
            'selectedDate.after_or_equal' => 'Non puoi prenotare nel passato.',
            'selectedStart.required' => 'Seleziona uno slot orario.',
        ]);

        $member = Auth::user()->member;

        if ($member === null) {
            session()->flash('error', 'Profilo membro non trovato.');

            return;
        }

        try {
            app(PtBookingService::class)->book(
                trainerId: $this->selectedTrainerId,
                memberId: $member->id,
                date: Carbon::parse($this->selectedDate),
                startTime: $this->selectedStart,
                endTime: $this->selectedEnd,
            );

            $this->reset(['selectedStart', 'selectedEnd']);
            $this->dispatch('booking-created');
            session()->flash('success', 'Sessione PT prenotata.');
            $this->loadAvailableSlots();
        } catch (BookingException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function cancelPtBooking(int $bookingId): void
    {
        abort_unless(Feature::active('pt_bookings'), 403);

        /** @var Member|null $member */
        $member = Auth::user()->member;

        if ($member === null) {
            session()->flash('error', 'Profilo membro non trovato.');

            return;
        }

        $booking = PtBooking::where('id', $bookingId)
            ->where('member_id', $member->id)
            ->firstOrFail();

        try {
            app(PtBookingService::class)->cancel(
                booking: $booking,
                cancelledBy: Auth::user(),
                reason: 'Annullata dall\'atleta.',
            );

            session()->flash('success', 'Prenotazione annullata.');
        } catch (BookingException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Corsi collettivi
    // -------------------------------------------------------------------------

    public function enrollClass(int $occurrenceId): void
    {
        abort_unless(Feature::active('group_classes'), 403);

        $this->enrollErrorId = 0;
        $this->enrollErrorMsg = '';

        $member = Auth::user()->member;

        if ($member === null) {
            session()->flash('error', 'Profilo membro non trovato.');

            return;
        }

        $occurrence = ClassOccurrence::findOrFail($occurrenceId);

        // Finestra di prenotazione
        $occurrenceStart = Carbon::parse($occurrence->date->toDateString().' '.substr($occurrence->start_time, 0, 8));
        $opensAt = $occurrenceStart->copy()->subDays((int) config('classes.booking_opens_days', 7));
        $closesAt = $occurrenceStart->copy()->subMinutes((int) config('classes.booking_closes_minutes', 30));

        if (now()->lt($opensAt)) {
            $this->enrollErrorId = $occurrenceId;
            $this->enrollErrorMsg = 'Le prenotazioni aprono il '.$opensAt->format('d/m/Y').'.';

            return;
        }

        if (now()->gt($closesAt)) {
            $this->enrollErrorId = $occurrenceId;
            $this->enrollErrorMsg = 'Prenotazioni chiuse (entro '.
                config('classes.booking_closes_minutes', 30)." min dall'inizio).";

            return;
        }

        try {
            $booking = app(ClassBookingService::class)->enroll($occurrence, $member);

            $message = $booking->status === 'confirmed'
                ? 'Iscrizione confermata!'
                : "Sei in lista d'attesa (posizione {$booking->position}).";

            session()->flash('success', $message);
        } catch (BookingException $e) {
            $this->enrollErrorId = $occurrenceId;
            $this->enrollErrorMsg = $e->getMessage();
        }
    }

    public function cancelClassBooking(int $bookingId): void
    {
        abort_unless(Feature::active('group_classes'), 403);

        /** @var Member|null $member */
        $member = Auth::user()->member;

        if ($member === null) {
            session()->flash('error', 'Profilo membro non trovato.');

            return;
        }

        $booking = ClassBooking::where('id', $bookingId)
            ->where('member_id', $member->id)
            ->firstOrFail();

        $occurrence = $booking->occurrence;
        $occurrenceStart = Carbon::parse($occurrence->date->toDateString().' '.substr($occurrence->start_time, 0, 8));
        $freeCancelUntil = $occurrenceStart->copy()->subHours((int) config('classes.free_cancel_hours', 3));

        if (now()->gt($freeCancelUntil)) {
            session()->flash('error', 'Cancellazione non disponibile (entro '.
                config('classes.free_cancel_hours', 3).' ore dall\'inizio).');

            return;
        }

        app(ClassBookingService::class)->cancel($booking);
        session()->flash('success', 'Iscrizione annullata.');
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render(): View
    {
        /** @var Member|null $member */
        $member = Auth::user()->member;

        $assignedTrainer = null;
        if ($member) {
            $activeMesocycle = Mesocycle::with('trainer')
                ->where('athlete_id', Auth::id())
                ->whereIn('status', ['active', 'in_progress'])
                ->latest('start_date')
                ->first();

            if ($activeMesocycle === null) {
                $activeMesocycle = Mesocycle::with('trainer')
                    ->where('athlete_id', Auth::id())
                    ->latest('start_date')
                    ->first();
            }

            $assignedTrainer = $activeMesocycle?->trainer;
        }

        $trainers = User::role(['trainer', 'gestore'])->orderBy('name')->get();

        $futurePtBookings = ($member && Feature::active('pt_bookings'))
            ? PtBooking::with('trainer')
                ->where('member_id', $member->id)
                ->where('booked_date', '>=', now()->toDateString())
                ->whereIn('status', ['pending', 'confirmed'])
                ->orderBy('booked_date')
                ->orderBy('start_time')
                ->get()
            : collect();

        // Occorrenze future prenotabili (solo se feature attiva)
        $futureClasses = Feature::active('group_classes')
            ? ClassOccurrence::with(['groupClass', 'trainer', 'confirmedBookings'])
                ->where('status', 'planned')
                ->where('date', '>=', now()->toDateString())
                ->orderBy('date')
                ->orderBy('start_time')
                ->get()
            : collect();

        // Iscrizioni attive dell'atleta a occorrenze future (solo se feature attiva)
        $myClassBookings = ($member && Feature::active('group_classes'))
            ? ClassBooking::with('occurrence.groupClass')
                ->where('member_id', $member->id)
                ->whereIn('status', ['confirmed', 'waitlisted'])
                ->whereHas('occurrence', fn ($q) => $q->where('date', '>=', now()->toDateString()))
                ->orderBy('created_at', 'desc')
                ->get()
            : collect();

        $myEnrolledOccurrenceIds = $myClassBookings->pluck('class_occurrence_id')->toArray();

        return view('livewire.athlete.booking', compact(
            'trainers',
            'futurePtBookings',
            'futureClasses',
            'myClassBookings',
            'myEnrolledOccurrenceIds',
            'member',
            'assignedTrainer',
        ))->layout('layouts.athlete');
    }
}
