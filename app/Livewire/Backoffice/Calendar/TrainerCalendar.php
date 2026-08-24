<?php

namespace App\Livewire\Backoffice\Calendar;

use App\Exceptions\BookingException;
use App\Models\ClassOccurrence;
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
    public string $weekStart = '';

    public string $selectedDate = '';

    public int $selectedTrainerId = 0;

    public bool $showBookingModal = false;

    public int $bookingMemberId = 0;

    public string $bookingStart = '';

    public string $bookingEnd = '';

    public string $bookingMemberSearch = '';

    public bool $showDetailModal = false;

    public ?int $detailBookingId = null;

    public string $detailType = ''; // 'pt' o 'class'

    public function mount(): void
    {
        $this->weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $this->selectedTrainerId = 0;
    }

    public function previousWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->subWeek()->toDateString();
        $this->dispatchCalendarRefresh();
    }

    public function nextWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->addWeek()->toDateString();
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
     * Comprende: finestre di disponibilità, prenotazioni PT, occorrenze corso.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getEventsForWeek(): array
    {
        $start = Carbon::parse($this->weekStart);
        $end = $start->copy()->endOfWeek(Carbon::SUNDAY);
        $events = [];

        $showAll = $this->selectedTrainerId === 0;

        // Disponibilità
        if (! $showAll) {
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
        }

        // Prenotazioni PT
        $ptBookings = PtBooking::with(['member', 'trainer'])
            ->when(! $showAll, fn ($q) => $q->where('trainer_id', $this->selectedTrainerId))
            ->whereBetween('booked_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('status', ['pending', 'confirmed'])
            ->get();

        foreach ($ptBookings as $booking) {
            $memberName = $booking->member ? $booking->member->full_name : 'N/D';
            $trainerLabel = $showAll && $booking->trainer ? ' ('.$booking->trainer->name.')' : '';

            $events[] = [
                'id' => 'pt_'.$booking->id,
                'title' => 'PT: '.$memberName.$trainerLabel,
                'start' => $booking->booked_date->toDateString().'T'.substr($booking->start_time, 0, 5),
                'end' => $booking->booked_date->toDateString().'T'.substr($booking->end_time, 0, 5),
                'color' => '#3b82f6',
                'extendedProps' => ['type' => 'pt', 'id' => $booking->id],
            ];
        }

        // Occorrenze corsi collettivi
        $occurrences = ClassOccurrence::with(['groupClass', 'trainer'])
            ->when(! $showAll, fn ($q) => $q->where('trainer_id', $this->selectedTrainerId))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('status', 'planned')
            ->get();

        foreach ($occurrences as $occurrence) {
            $className = $occurrence->groupClass->name;
            $trainerLabel = $showAll && $occurrence->trainer ? ' ('.$occurrence->trainer->name.')' : '';

            $events[] = [
                'id' => 'class_'.$occurrence->id,
                'title' => $className.$trainerLabel,
                'start' => $occurrence->date->format('Y-m-d').'T'.substr($occurrence->start_time, 0, 5),
                'end' => $occurrence->date->format('Y-m-d').'T'.substr($occurrence->end_time, 0, 5),
                'color' => '#f59e0b',
                'extendedProps' => ['type' => 'class', 'id' => $occurrence->id],
            ];
        }

        return $events;
    }

    public function openBookingModal(string $date, string $start, string $end): void
    {
        $this->selectedDate = $date;
        $this->bookingStart = $start;
        $this->bookingEnd = $end;
        $this->bookingMemberId = 0;
        $this->showBookingModal = true;
    }

    public function createBooking(): void
    {
        if ($this->selectedTrainerId === 0) {
            $this->addError('bookingStart', 'Seleziona un trainer specifico per creare la prenotazione.');

            return;
        }

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

    public function openDetailModal(string $type, int $id): void
    {
        $this->detailType = $type;
        $this->detailBookingId = $id;
        $this->showDetailModal = true;
    }

    public function cancelBooking(int $bookingId): void
    {
        $booking = PtBooking::findOrFail($bookingId);

        abort_unless(
            Auth::user()->hasRole('gestore') || $booking->trainer_id === Auth::id(),
            403
        );

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
        $trainers = User::role(['trainer', 'gestore'])->orderBy('name')->get();

        $detailBooking = null;
        if ($this->showDetailModal && $this->detailBookingId) {
            $detailBooking = match ($this->detailType) {
                'pt' => PtBooking::with(['member', 'trainer'])->find($this->detailBookingId),
                'class' => ClassOccurrence::with(['groupClass', 'trainer', 'confirmedBookings.member'])->find($this->detailBookingId),
                default => null,
            };
        }

        $members = Member::when($this->bookingMemberSearch, fn ($q) => $q->where(fn ($q2) => $q2->where('first_name', 'like', '%'.$this->bookingMemberSearch.'%')
            ->orWhere('last_name', 'like', '%'.$this->bookingMemberSearch.'%')
        ))->orderBy('last_name')->limit(20)->get();

        $events = $this->getEventsForWeek();
        $weekEnd = Carbon::parse($this->weekStart)->endOfWeek(Carbon::SUNDAY)->toDateString();

        return view('livewire.backoffice.calendar.trainer-calendar', compact(
            'trainers', 'detailBooking', 'members', 'events', 'weekEnd'
        ))->layout('layouts.backoffice')->layoutData(['page_title' => 'Calendario prenotazioni']);
    }
}
