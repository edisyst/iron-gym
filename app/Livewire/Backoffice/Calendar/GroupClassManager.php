<?php

namespace App\Livewire\Backoffice\Calendar;

use App\Models\ClassBooking;
use App\Models\GroupClass;
use App\Models\User;
use App\Services\ClassBookingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class GroupClassManager extends Component
{
    use WithPagination;

    // Filtri lista
    public string $search = '';

    public string $filterStatus = 'scheduled';

    // Form creazione/modifica
    public bool $showForm = false;

    public ?int $editingClassId = null;

    public int $formTrainerId = 0;

    public string $formName = '';

    public string $formDescription = '';

    public string $formScheduledAt = '';

    public int $formDurationMinutes = 60;

    public int $formMaxParticipants = 10;

    // Pannello dettaglio corso
    public bool $showDetail = false;

    public ?int $selectedClassId = null;

    // -------------------------------------------------------------------------
    // Lifecycle
    // -------------------------------------------------------------------------

    public function mount(): void
    {
        $this->formTrainerId = (int) Auth::id();
    }

    // -------------------------------------------------------------------------
    // Aggiornamenti filtri
    // -------------------------------------------------------------------------

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    // -------------------------------------------------------------------------
    // Form CRUD
    // -------------------------------------------------------------------------

    /**
     * Apre il form: se $id è valorizzato carica i dati per la modifica.
     */
    public function openForm(?int $id = null): void
    {
        $this->showDetail = false;

        if ($id !== null) {
            $class = GroupClass::findOrFail($id);
            $this->editingClassId = $class->id;
            $this->formTrainerId = $class->trainer_id;
            $this->formName = $class->name;
            $this->formDescription = $class->description ?? '';
            $this->formScheduledAt = $class->scheduled_at->format('Y-m-d\TH:i');
            $this->formDurationMinutes = $class->duration_minutes;
            $this->formMaxParticipants = $class->max_participants;
        } else {
            $this->editingClassId = null;
            $this->reset([
                'formName', 'formDescription', 'formScheduledAt',
            ]);
            $this->formDurationMinutes = 60;
            $this->formMaxParticipants = 10;
            $this->formTrainerId = (int) Auth::id();
        }

        $this->showForm = true;
    }

    /**
     * Salva il corso (creazione o modifica).
     */
    public function save(): void
    {
        $this->validate([
            'formTrainerId' => 'required|integer|exists:users,id',
            'formName' => 'required|string|max:128',
            'formDescription' => 'nullable|string',
            'formScheduledAt' => 'required|date|after:now',
            'formDurationMinutes' => 'required|integer|min:15|max:480',
            'formMaxParticipants' => 'required|integer|min:1|max:100',
        ], [
            'formName.required' => 'Il nome del corso è obbligatorio.',
            'formScheduledAt.required' => 'Data e ora sono obbligatorie.',
            'formScheduledAt.after' => 'Il corso deve essere programmato in futuro.',
        ]);

        $data = [
            'trainer_id' => $this->formTrainerId,
            'name' => $this->formName,
            'description' => $this->formDescription ?: null,
            'scheduled_at' => $this->formScheduledAt,
            'duration_minutes' => $this->formDurationMinutes,
            'max_participants' => $this->formMaxParticipants,
        ];

        if ($this->editingClassId !== null) {
            GroupClass::findOrFail($this->editingClassId)->update($data);
            session()->flash('success', 'Corso aggiornato.');
        } else {
            GroupClass::create($data);
            session()->flash('success', 'Corso creato.');
        }

        $this->showForm = false;
        $this->editingClassId = null;
    }

    /**
     * Elimina (o cancella) un corso se non ha partecipanti confermati.
     */
    public function deleteClass(int $id): void
    {
        $class = GroupClass::findOrFail($id);

        $hasConfirmed = $class->confirmedBookings()->exists();

        if ($hasConfirmed) {
            // Imposta status cancelled invece di eliminare fisicamente
            $class->update([
                'status' => 'cancelled',
                'cancellation_reason' => 'Corso cancellato dal gestore.',
            ]);
            session()->flash('success', 'Corso cancellato (aveva partecipanti iscritti).');
        } else {
            $class->delete();
            session()->flash('success', 'Corso eliminato.');
        }

        if ($this->selectedClassId === $id) {
            $this->showDetail = false;
            $this->selectedClassId = null;
        }
    }

    // -------------------------------------------------------------------------
    // Dettaglio corso
    // -------------------------------------------------------------------------

    /**
     * Apre il pannello dettaglio corso con lista iscritti e waitlist.
     */
    public function openDetail(int $id): void
    {
        $this->selectedClassId = $id;
        $this->showForm = false;
        $this->showDetail = true;
    }

    /**
     * Rimuove un partecipante dal corso e promuove il primo in waitlist se necessario.
     */
    public function removeParticipant(int $bookingId): void
    {
        $booking = ClassBooking::findOrFail($bookingId);
        app(ClassBookingService::class)->cancel($booking);
        session()->flash('success', 'Partecipante rimosso.');
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render(): View
    {
        $classes = GroupClass::with(['trainer', 'confirmedBookings'])
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%')
            )
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus)
            )
            ->orderByDesc('scheduled_at')
            ->paginate(20);

        $trainers = User::role(['trainer', 'gestore'])->orderBy('name')->get();

        // Dettaglio corso selezionato con eager loading completo
        $selectedClass = null;
        if ($this->showDetail && $this->selectedClassId) {
            $selectedClass = GroupClass::with([
                'trainer',
                'confirmedBookings.member',
                'waitlist.member',
            ])->find($this->selectedClassId);
        }

        return view('livewire.backoffice.calendar.group-class-manager', compact(
            'classes', 'trainers', 'selectedClass'
        ))->layout('layouts.backoffice')->layoutData(['page_title' => 'Corsi collettivi']);
    }
}
