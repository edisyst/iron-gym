<?php

namespace App\Livewire\Backoffice\Calendar;

use App\Models\ClassBooking;
use App\Models\ClassOccurrence;
use App\Models\GroupClass;
use App\Models\User;
use App\Services\ClassBookingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class GroupClassManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterStatus = 'planned';

    public bool $showForm = false;

    public ?int $editingClassId = null; // ID occorrenza in modifica

    public int $formTrainerId = 0;

    public string $formName = '';

    public string $formDescription = '';

    public string $formScheduledAt = '';

    public int $formDurationMinutes = 60;

    public int $formMaxParticipants = 10; // capacity sull'occorrenza

    public bool $showDetail = false;

    public ?int $selectedClassId = null; // ID occorrenza selezionata per il dettaglio

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

    public function openForm(?int $id = null): void
    {
        $this->showDetail = false;

        if ($id !== null) {
            $occurrence = ClassOccurrence::with('groupClass')->findOrFail($id);
            $this->editingClassId = $occurrence->id;
            $this->formTrainerId = $occurrence->trainer_id;
            $this->formName = $occurrence->groupClass->name;
            $this->formDescription = $occurrence->groupClass->description ?? '';
            $this->formScheduledAt = $occurrence->date->format('Y-m-d').'T'.substr($occurrence->start_time, 0, 5);
            $this->formDurationMinutes = $occurrence->groupClass->duration_minutes;
            $this->formMaxParticipants = $occurrence->capacity;
        } else {
            $this->editingClassId = null;
            $this->reset(['formName', 'formDescription', 'formScheduledAt']);
            $this->formDurationMinutes = 60;
            $this->formMaxParticipants = 10;
            $this->formTrainerId = (int) Auth::id();
        }

        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless(Auth::user()->hasAnyRole(['gestore', 'trainer']), 403);

        $this->validate([
            'formTrainerId'       => 'required|integer|exists:users,id',
            'formName'            => 'required|string|max:128',
            'formDescription'     => 'nullable|string',
            'formScheduledAt'     => 'required|date|after:now',
            'formDurationMinutes' => 'required|integer|min:15|max:480',
            'formMaxParticipants' => 'required|integer|min:1|max:100',
        ], [
            'formName.required'       => 'Il nome del corso è obbligatorio.',
            'formScheduledAt.required' => 'Data e ora sono obbligatorie.',
            'formScheduledAt.after'    => 'Il corso deve essere programmato in futuro.',
        ]);

        $scheduledAt = \Carbon\Carbon::parse($this->formScheduledAt);
        $endTime = $scheduledAt->copy()->addMinutes($this->formDurationMinutes)->format('H:i:s');

        // Trova o crea la definizione del corso per slug
        $slug = Str::slug($this->formName);
        $groupClass = GroupClass::firstOrCreate(
            ['slug' => $slug],
            [
                'name'             => $this->formName,
                'description'      => $this->formDescription ?: null,
                'duration_minutes' => $this->formDurationMinutes,
                'default_capacity' => $this->formMaxParticipants,
                'is_active'        => true,
            ]
        );

        // Aggiorna la descrizione se la definizione esisteva già
        if (! $groupClass->wasRecentlyCreated) {
            $groupClass->update([
                'description'      => $this->formDescription ?: null,
                'duration_minutes' => $this->formDurationMinutes,
            ]);
        }

        // Abilita il trainer sul corso
        $groupClass->trainers()->syncWithoutDetaching([$this->formTrainerId]);

        if ($this->editingClassId !== null) {
            $occurrence = ClassOccurrence::findOrFail($this->editingClassId);
            $occurrence->update([
                'trainer_id'  => $this->formTrainerId,
                'date'        => $scheduledAt->toDateString(),
                'start_time'  => $scheduledAt->format('H:i:s'),
                'end_time'    => $endTime,
                'capacity'    => $this->formMaxParticipants,
            ]);
            session()->flash('success', 'Corso aggiornato.');
        } else {
            ClassOccurrence::create([
                'group_class_id'    => $groupClass->id,
                'class_schedule_id' => null,
                'trainer_id'        => $this->formTrainerId,
                'date'              => $scheduledAt->toDateString(),
                'start_time'        => $scheduledAt->format('H:i:s'),
                'end_time'          => $endTime,
                'capacity'          => $this->formMaxParticipants,
                'status'            => 'planned',
            ]);
            session()->flash('success', 'Corso creato.');
        }

        $this->showForm = false;
        $this->editingClassId = null;
    }

    public function deleteClass(int $id): void
    {
        abort_unless(Auth::user()->hasAnyRole(['gestore', 'trainer']), 403);

        $occurrence = ClassOccurrence::findOrFail($id);

        $hasConfirmed = $occurrence->confirmedBookings()->exists();

        if ($hasConfirmed) {
            $occurrence->update([
                'status'              => 'cancelled',
                'cancellation_reason' => 'Corso cancellato dal gestore.',
            ]);
            session()->flash('success', 'Corso cancellato (aveva partecipanti iscritti).');
        } else {
            $occurrence->delete();
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

    public function openDetail(int $id): void
    {
        $this->selectedClassId = $id;
        $this->showForm = false;
        $this->showDetail = true;
    }

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
        $occurrences = ClassOccurrence::with(['groupClass', 'trainer', 'confirmedBookings'])
            ->when($this->search, fn ($q) => $q->whereHas('groupClass', fn ($q2) => $q2->where('name', 'like', '%'.$this->search.'%')
            ))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderByDesc('date')
            ->orderByDesc('start_time')
            ->paginate(20);

        $trainers = User::role(['trainer', 'gestore'])->orderBy('name')->get();

        $selectedClass = null;
        if ($this->showDetail && $this->selectedClassId) {
            $selectedClass = ClassOccurrence::with([
                'groupClass',
                'trainer',
                'confirmedBookings.member',
                'waitlist.member',
            ])->find($this->selectedClassId);
        }

        return view('livewire.backoffice.calendar.group-class-manager', [
            'classes'       => $occurrences,
            'trainers'      => $trainers,
            'selectedClass' => $selectedClass,
        ])->layout('layouts.backoffice')->layoutData(['page_title' => 'Corsi collettivi']);
    }
}
