<?php

namespace App\Livewire\Backoffice\Calendar;

use App\Models\ClassOccurrence;
use App\Models\ClassSchedule;
use App\Models\GroupClass;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ClassScheduleManager extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public ?int $editingId = null;

    public int $formGroupClassId = 0;

    public int $formWeekday = 0;

    public string $formStartTime = '09:00';

    public int $formTrainerId = 0;

    public string $formValidFrom = '';

    public string $formValidUntil = '';

    public bool $formIsActive = true;

    // -------------------------------------------------------------------------
    // Lifecycle
    // -------------------------------------------------------------------------

    public function mount(): void
    {
        $this->formTrainerId = (int) Auth::id();
        $this->formValidFrom = today()->toDateString();
    }

    // -------------------------------------------------------------------------
    // Form CRUD
    // -------------------------------------------------------------------------

    public function openForm(?int $id = null): void
    {
        if ($id !== null) {
            $schedule = ClassSchedule::findOrFail($id);
            $this->editingId = $schedule->id;
            $this->formGroupClassId = $schedule->group_class_id;
            $this->formWeekday = $schedule->weekday;
            $this->formStartTime = substr($schedule->start_time, 0, 5);
            $this->formTrainerId = $schedule->trainer_id ?? (int) Auth::id();
            $this->formValidFrom = $schedule->valid_from
                ? $schedule->valid_from->format('Y-m-d')
                : today()->toDateString();
            $this->formValidUntil = $schedule->valid_until
                ? $schedule->valid_until->format('Y-m-d')
                : '';
            $this->formIsActive = $schedule->is_active;
        } else {
            $this->editingId = null;
            $this->reset(['formGroupClassId', 'formWeekday', 'formValidUntil']);
            $this->formStartTime = '09:00';
            $this->formIsActive = true;
            $this->formTrainerId = (int) Auth::id();
            $this->formValidFrom = today()->toDateString();
        }

        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless(Auth::user()->hasAnyRole(['gestore', 'trainer']), 403);

        $this->validate([
            'formGroupClassId' => 'required|integer|exists:group_classes,id',
            'formWeekday' => 'required|integer|min:0|max:6',
            'formStartTime' => 'required|date_format:H:i',
            'formTrainerId' => 'required|integer|exists:users,id',
            'formValidFrom' => 'required|date',
            'formValidUntil' => 'nullable|date|after:formValidFrom',
        ], [
            'formGroupClassId.required' => 'Seleziona un corso.',
            'formGroupClassId.exists' => 'Corso non trovato.',
            'formStartTime.date_format' => 'Orario non valido (HH:MM).',
            'formValidUntil.after' => 'La data di fine deve essere successiva alla data di inizio.',
        ]);

        $data = [
            'group_class_id' => $this->formGroupClassId,
            'weekday' => $this->formWeekday,
            'start_time' => $this->formStartTime.':00',
            'trainer_id' => $this->formTrainerId,
            'valid_from' => $this->formValidFrom,
            'valid_until' => $this->formValidUntil ?: null,
            'is_active' => $this->formIsActive,
        ];

        if ($this->editingId !== null) {
            ClassSchedule::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Palinsesto aggiornato.');
        } else {
            ClassSchedule::create($data);
            session()->flash('success', 'Palinsesto creato.');
        }

        $this->showForm = false;
        $this->editingId = null;
    }

    public function toggleActive(int $id): void
    {
        abort_unless(Auth::user()->hasAnyRole(['gestore', 'trainer']), 403);

        $schedule = ClassSchedule::findOrFail($id);
        $schedule->update(['is_active' => ! $schedule->is_active]);
    }

    public function deleteSchedule(int $id): void
    {
        abort_unless(Auth::user()->hasAnyRole(['gestore', 'trainer']), 403);

        $hasFuture = ClassOccurrence::where('class_schedule_id', $id)
            ->whereDate('date', '>=', today())
            ->where('status', 'planned')
            ->exists();

        if ($hasFuture) {
            session()->flash('error', 'Impossibile eliminare: ci sono occorrenze future pianificate. Disattiva il palinsesto.');

            return;
        }

        ClassSchedule::findOrFail($id)->delete();
        session()->flash('success', 'Palinsesto eliminato.');
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render(): View
    {
        $schedules = ClassSchedule::with(['groupClass', 'trainer'])
            ->orderBy('group_class_id')
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->paginate(20);

        $groupClasses = GroupClass::active()->orderBy('name')->get();
        $trainers = User::role(['trainer', 'gestore'])->orderBy('name')->get();

        $weekdayLabels = [
            0 => 'Lunedì',
            1 => 'Martedì',
            2 => 'Mercoledì',
            3 => 'Giovedì',
            4 => 'Venerdì',
            5 => 'Sabato',
            6 => 'Domenica',
        ];

        return view('livewire.backoffice.calendar.class-schedule-manager', compact(
            'schedules', 'groupClasses', 'trainers', 'weekdayLabels'
        ))->layout('layouts.backoffice')->layoutData(['page_title' => 'Palinsesto corsi']);
    }
}
