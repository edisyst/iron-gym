<?php

namespace App\Livewire\Athlete;

use App\Exceptions\BookingException;
use App\Models\ClassBooking;
use App\Models\GroupClass;
use App\Models\Member;
use App\Models\PtBooking;
use App\Models\TrainerAvailability;
use App\Models\User;
use App\Services\ClassBookingService;
use App\Services\PtBookingService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Booking extends Component
{
    public string $activeTab = 'pt'; // 'pt' | 'classes'

    // Tab PT
    public int $selectedTrainerId = 0;

    public string $selectedDate = '';

    /** @var Collection<int, array{start: string, end: string}> */
    public Collection $availableSlots;

    public string $selectedStart = '';

    public string $selectedEnd = '';

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

        // Reset selezione slot quando cambia la data
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

    /**
     * Carica gli slot orari disponibili per il trainer e la data selezionati.
     */
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

    /**
     * Seleziona uno slot orario dalla griglia.
     */
    public function selectSlot(string $start, string $end): void
    {
        $this->selectedStart = $start;
        $this->selectedEnd = $end;
    }

    // -------------------------------------------------------------------------
    // Prenotazioni PT
    // -------------------------------------------------------------------------

    /**
     * Prenota la sessione PT con lo slot selezionato.
     */
    public function bookPt(): void
    {
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

    /**
     * Annulla una prenotazione PT dell'atleta autenticato.
     */
    public function cancelPtBooking(int $bookingId): void
    {
        /** @var Member $member */
        $member = Auth::user()->member;

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

    /**
     * Iscrive l'atleta autenticato a un corso collettivo.
     */
    public function enrollClass(int $classId): void
    {
        $member = Auth::user()->member;

        if ($member === null) {
            session()->flash('error', 'Profilo membro non trovato.');

            return;
        }

        $class = GroupClass::findOrFail($classId);

        try {
            $booking = app(ClassBookingService::class)->enroll($class, $member);

            $message = $booking->status === 'confirmed'
                ? 'Iscrizione confermata!'
                : "Sei in lista d'attesa (posizione {$booking->position}).";

            session()->flash('success', $message);
        } catch (BookingException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Annulla l'iscrizione dell'atleta a un corso.
     */
    public function cancelClassBooking(int $bookingId): void
    {
        /** @var Member $member */
        $member = Auth::user()->member;

        $booking = ClassBooking::where('id', $bookingId)
            ->where('member_id', $member->id)
            ->firstOrFail();

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

        // Trainer disponibili per la prenotazione PT
        $trainers = User::role(['trainer', 'gestore'])->orderBy('name')->get();

        // Prenotazioni PT future dell'atleta
        $futurePtBookings = $member
            ? PtBooking::with('trainer')
                ->where('member_id', $member->id)
                ->where('booked_date', '>=', now()->toDateString())
                ->whereIn('status', ['pending', 'confirmed'])
                ->orderBy('booked_date')
                ->orderBy('start_time')
                ->get()
            : collect();

        // Corsi collettivi futuri con eager load iscrizioni
        $futureClasses = GroupClass::with(['trainer', 'confirmedBookings'])
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>', now())
            ->orderBy('scheduled_at')
            ->get();

        // Le mie iscrizioni ai corsi
        $myClassBookings = $member
            ? ClassBooking::with('groupClass.trainer')
                ->where('member_id', $member->id)
                ->whereIn('status', ['confirmed', 'waitlisted'])
                ->whereHas('groupClass', fn ($q) => $q->where('scheduled_at', '>', now()))
                ->orderBy('created_at', 'desc')
                ->get()
            : collect();

        // Set degli id corsi a cui sono già iscritto (per disabilitare il bottone)
        $myEnrolledClassIds = $myClassBookings->pluck('class_id')->toArray();

        return view('livewire.athlete.booking', compact(
            'trainers',
            'futurePtBookings',
            'futureClasses',
            'myClassBookings',
            'myEnrolledClassIds',
            'member',
        ))->layout('layouts.athlete');
    }
}
