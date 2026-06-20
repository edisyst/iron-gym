<?php

namespace App\Livewire\Backoffice\Calendar;

use App\Exceptions\BookingException;
use App\Models\GroupClass;
use App\Models\Member;
use App\Models\PtBooking;
use App\Models\TrainerAvailability;
use App\Models\User;
use App\Services\PtBookingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class TrainerCalendar extends Component
{
    // Lunedì della settimana visualizzata (YYYY-MM-DD)
    public string $weekStart = '';

    // Data selezionata per apertura modale (YYYY-MM-DD)
    public string $selectedDate = '';

    // Trainer selezionato nel filtro (gestore può switchare)
    public int $selectedTrainerId = 0;

    // Modale creazione prenotazione PT
    public bool $showBookingModal = false;

    public int $bookingMemberId = 0;

    public string $bookingStart = '';

    public string $bookingEnd = '';

    public string $bookingMemberSearch = '';

    // Modale dettaglio booking
    public bool $showDetailModal = false;

    public ?int $detailBookingId = null;

    public string $detailType = ''; // 'pt' o 'class'

    public function mount(): void
    {
        // Inizia dalla settimana corrente (lunedì ISO)
        $this->weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $this->selectedTrainerId = (int) Auth::id();
    }

    public function previousWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)
            ->subWeek()
            ->toDateString();

        $this->dispatchCalendarRefresh();
    }

    public function nextWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)
            ->addWeek()
            ->toDateString();

        $this->dispatchCalendarRefresh();
    }

    public function updatedSelectedTrainerId(): void
    {
        $this->dispatchCalendarRefresh();
    }

    private function dispatchCalendarRefresh(): void
    {
        $this->dispatch('calendar-refresh', [
            'events' => $this->getEventsForWeek(),
            'weekStart' => $this->weekStart,
        ]);
    }

    /**
     * Restituisce eventi in formato FullCalendar per la settimana corrente.
     * Comprende: finestre di disponibilità, prenotazioni PT, corsi collettivi.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getEventsForWeek(): array
    {
        $start = Carbon::parse($this->weekStart);
        $end = $start->copy()->endOfWeek(Carbon::SUNDAY);
        $events = [];

        // Disponibilità (slot aperti) — colore verde
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $slots = TrainerAvailability::where('trainer_id', $this->selectedTrainerId)
                ->forDate($day)
                ->where('is_available', true)
                ->get();

            foreach ($slots as $slot) {
                $events[] = [
                    'id' => 'avail_'.$slot->id,
                    'title' => 'Disponibile',
                    'start' => $day->toDateString().'T'.substr($slot->start_time, 0, 5),
                    'end' => $day->toDateString().'T'.substr($slot->end_time, 0, 5),
                    'color' => '#22c55e',
                    'display' => 'background',
                    'extendedProps' => ['type' => 'availability', 'id' => $slot->id],
                ];
            }
        }

        // Prenotazioni PT — colore blu
        $ptBookings = PtBooking::with('member')
            ->where('trainer_id', $this->selectedTrainerId)
            ->whereBetween('booked_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('status', ['pending', 'confirmed'])
            ->get();

        foreach ($ptBookings as $booking) {
            $memberName = $booking->member
                ? $booking->member->full_name
                : 'N/D';

            $events[] = [
                'id' => 'pt_'.$booking->id,
                'title' => 'PT: '.$memberName,
                'start' => $booking->booked_date->toDateString().'T'.substr($booking->start_time, 0, 5),
                'end' => $booking->booked_date->toDateString().'T'.substr($booking->end_time, 0, 5),
                'color' => '#3b82f6',
                'extendedProps' => ['type' => 'pt', 'id' => $booking->id],
            ];
        }

        // Corsi collettivi — colore arancione ambra
        $classes = GroupClass::with('trainer')
            ->where('trainer_id', $this->selectedTrainerId)
            ->whereBetween('scheduled_at', [
                $start->toDateString().' 00:00:00',
                $end->toDateString().' 23:59:59',
            ])
            ->where('status', 'scheduled')
            ->get();

        foreach ($classes as $class) {
            $classEnd = $class->scheduled_at->copy()->addMinutes($class->duration_minutes);

            $events[] = [
                'id' => 'class_'.$class->id,
                'title' => $class->name,
                'start' => $class->scheduled_at->format('Y-m-d\TH:i'),
                'end' => $classEnd->format('Y-m-d\TH:i'),
                'color' => '#f59e0b',
                'extendedProps' => ['type' => 'class', 'id' => $class->id],
            ];
        }

        return $events;
    }

    /**
     * Apre la modale di creazione prenotazione PT con data e orari precompilati.
     */
    public function openBookingModal(string $date, string $start, string $end): void
    {
        $this->selectedDate = $date;
        $this->bookingStart = $start;
        $this->bookingEnd = $end;
        $this->bookingMemberId = 0;
        $this->showBookingModal = true;
    }

    /**
     * Crea una prenotazione PT dopo validazione.
     */
    public function createBooking(): void
    {
        $this->validate([
            'bookingMemberId' => 'required|integer|exists:members,id',
            'selectedDate' => 'required|date',
            'bookingStart' => 'required|date_format:H:i',
            'bookingEnd' => 'required|date_format:H:i|after:bookingStart',
        ], [
            'bookingMemberId.required' => 'Seleziona un tesserato.',
            'bookingEnd.after' => "L'ora di fine deve essere successiva all'ora di inizio.",
        ]);

        try {
            app(PtBookingService::class)->book(
                trainerId: $this->selectedTrainerId,
                memberId: $this->bookingMemberId,
                date: Carbon::parse($this->selectedDate),
                startTime: $this->bookingStart,
                endTime: $this->bookingEnd,
            );

            $this->showBookingModal = false;
            $this->dispatch('booking-created');
            session()->flash('success', 'Prenotazione PT creata con successo.');
        } catch (BookingException $e) {
            $this->addError('bookingStart', $e->getMessage());
        }
    }

    /**
     * Apre la modale di dettaglio per una prenotazione PT o un corso.
     */
    public function openDetailModal(string $type, int $id): void
    {
        $this->detailType = $type;
        $this->detailBookingId = $id;
        $this->showDetailModal = true;
    }

    /**
     * Annulla una prenotazione PT dal calendario.
     */
    public function cancelBooking(int $bookingId): void
    {
        $booking = PtBooking::findOrFail($bookingId);

        try {
            app(PtBookingService::class)->cancel(
                booking: $booking,
                cancelledBy: Auth::user(),
                reason: 'Annullata dal trainer/gestore dal calendario.',
            );

            $this->showDetailModal = false;
            $this->dispatch('booking-cancelled');
            session()->flash('success', 'Prenotazione annullata.');
        } catch (BookingException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render(): View
    {
        // Trainer disponibili per il filtro (gestore vede tutti, trainer solo se stesso)
        $trainers = User::role(['trainer', 'gestore'])->orderBy('name')->get();

        // Dettaglio booking per la modale
        $detailBooking = null;
        if ($this->showDetailModal && $this->detailBookingId) {
            $detailBooking = match ($this->detailType) {
                'pt' => PtBooking::with(['member', 'trainer'])->find($this->detailBookingId),
                'class' => GroupClass::with(['trainer', 'confirmedBookings.member'])->find($this->detailBookingId),
                default => null,
            };
        }

        // Membri per la modale di creazione (filtro per ricerca)
        $members = Member::when($this->bookingMemberSearch, fn ($q) => $q->where(fn ($q2) => $q2->where('first_name', 'like', '%'.$this->bookingMemberSearch.'%')
            ->orWhere('last_name', 'like', '%'.$this->bookingMemberSearch.'%')
        )
        )->orderBy('last_name')->limit(20)->get();

        $events = $this->getEventsForWeek();

        $weekEnd = Carbon::parse($this->weekStart)->endOfWeek(Carbon::SUNDAY)->toDateString();

        return view('livewire.backoffice.calendar.trainer-calendar', compact(
            'trainers', 'detailBooking', 'members', 'events', 'weekEnd'
        ))->layout('layouts.backoffice')->layoutData(['page_title' => 'Calendario prenotazioni']);
    }
}
