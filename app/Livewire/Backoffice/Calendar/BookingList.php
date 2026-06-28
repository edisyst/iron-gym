<?php

namespace App\Livewire\Backoffice\Calendar;

use App\Exceptions\BookingException;
use App\Models\PtBooking;
use App\Models\User;
use App\Services\PtBookingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class BookingList extends Component
{
    use WithPagination;

    // Filtri
    public string $filterDate = '';

    public int $filterTrainerId = 0;

    public string $filterStatus = '';

    public string $search = '';

    // Modale annullamento
    public bool $showCancelModal = false;

    public int $cancelBookingId = 0;

    public string $cancelReason = '';

    /**
     * Resetta la paginazione al cambio filtri.
     */
    public function updatedFilterDate(): void
    {
        $this->resetPage();
    }

    public function updatedFilterTrainerId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Conferma una prenotazione pending.
     */
    public function confirm(int $bookingId): void
    {
        $user = Auth::user();

        $query = PtBooking::where('id', $bookingId)->where('status', 'pending');

        if (! $user->hasRole('gestore')) {
            $query->where('trainer_id', $user->id);
        }

        $query->update(['status' => 'confirmed']);

        session()->flash('success', 'Prenotazione confermata.');
    }

    /**
     * Apre la modale di conferma annullamento.
     */
    public function openCancelModal(int $id): void
    {
        $this->cancelBookingId = $id;
        $this->cancelReason = '';
        $this->showCancelModal = true;
    }

    /**
     * Esegue l'annullamento con il motivo inserito.
     */
    public function cancel(): void
    {
        $this->validate([
            'cancelReason' => 'required|string|min:5|max:500',
        ], [
            'cancelReason.required' => 'Inserisci un motivo per l\'annullamento.',
            'cancelReason.min' => 'Il motivo deve essere di almeno 5 caratteri.',
        ]);

        $booking = PtBooking::findOrFail($this->cancelBookingId);

        try {
            app(PtBookingService::class)->cancel(
                booking: $booking,
                cancelledBy: Auth::user(),
                reason: $this->cancelReason,
            );

            $this->showCancelModal = false;
            $this->reset(['cancelBookingId', 'cancelReason']);
            session()->flash('success', 'Prenotazione annullata.');
        } catch (BookingException $e) {
            $this->addError('cancelReason', $e->getMessage());
        }
    }

    public function render(): View
    {
        $bookings = PtBooking::with(['trainer', 'member'])
            ->when($this->filterDate, fn ($q) => $q->where('booked_date', $this->filterDate))
            ->when($this->filterTrainerId, fn ($q) => $q->where('trainer_id', $this->filterTrainerId))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->search, fn ($q) => $q->whereHas('member', fn ($mq) => $mq->where('first_name', 'like', '%'.$this->search.'%')
                ->orWhere('last_name', 'like', '%'.$this->search.'%')
            )
            )
            ->orderByDesc('booked_date')
            ->orderBy('start_time')
            ->paginate(20);

        $trainers = User::role(['trainer', 'gestore'])->orderBy('name')->get();

        $statusLabels = [
            'pending' => 'In attesa',
            'confirmed' => 'Confermata',
            'cancelled' => 'Annullata',
            'completed' => 'Completata',
            'no_show' => 'No show',
        ];

        return view('livewire.backoffice.calendar.booking-list', compact(
            'bookings', 'trainers', 'statusLabels'
        ))->layout('layouts.backoffice')->layoutData(['page_title' => 'Prenotazioni PT']);
    }
}
