<?php

namespace App\Livewire\Backoffice\Settings;

use App\Models\OpeningHour;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class OpeningHoursManager extends Component
{
    // -------------------------------------------------------------------------
    // Form slot ricorrente — aggiunta
    // -------------------------------------------------------------------------

    public bool $showAddSlot = false;

    public int $newDayOfWeek = 0;

    public string $newStartTime = '';

    public string $newEndTime = '';

    // -------------------------------------------------------------------------
    // Form slot ricorrente — modifica inline
    // -------------------------------------------------------------------------

    public ?int $editingSlotId = null;

    public int $editSlotDay = 0;

    public string $editSlotStart = '';

    public string $editSlotEnd = '';

    // -------------------------------------------------------------------------
    // Form eccezione / festività — aggiunta
    // -------------------------------------------------------------------------

    public bool $showAddOverride = false;

    public string $newDate = '';

    public string $newOverrideStart = '';

    public string $newOverrideEnd = '';

    public bool $newIsOpen = true;

    public bool $newIsAnnual = false;

    public string $newNotes = '';

    // -------------------------------------------------------------------------
    // Form eccezione / festività — modifica inline
    // -------------------------------------------------------------------------

    public ?int $editingOverrideId = null;

    public string $editOverrideDate = '';

    public string $editOverrideStart = '';

    public string $editOverrideEnd = '';

    public bool $editOverrideIsOpen = true;

    public bool $editOverrideIsAnnual = false;

    public string $editOverrideNotes = '';

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render(): View
    {
        $slots = OpeningHour::recurring()
            ->orderBy('day_of_week')
            ->get();

        $overrides = OpeningHour::overrides()
            ->orderByRaw('is_annual DESC')
            ->orderBy('specific_date')
            ->get();

        $daysOfWeek = [
            0 => 'Lunedì',
            1 => 'Martedì',
            2 => 'Mercoledì',
            3 => 'Giovedì',
            4 => 'Venerdì',
            5 => 'Sabato',
            6 => 'Domenica',
        ];

        $canEdit = Auth::user()->hasAnyRole(['gestore', 'receptionist']);

        return view('livewire.backoffice.settings.opening-hours-manager', compact(
            'slots', 'overrides', 'daysOfWeek', 'canEdit'
        ))->layout('layouts.backoffice')->layoutData(['page_title' => 'Orari di apertura']);
    }

    // -------------------------------------------------------------------------
    // Slot ricorrenti — aggiunta
    // -------------------------------------------------------------------------

    public function addSlot(): void
    {
        $this->assertCanEdit();

        $this->validate([
            'newDayOfWeek' => 'required|integer|min:0|max:6',
            'newStartTime' => 'required|date_format:H:i',
            'newEndTime' => 'required|date_format:H:i|after:newStartTime',
        ]);

        OpeningHour::create([
            'day_of_week' => $this->newDayOfWeek,
            'specific_date' => null,
            'is_annual' => false,
            'start_time' => $this->newStartTime,
            'end_time' => $this->newEndTime,
            'is_open' => true,
        ]);

        $this->reset(['newDayOfWeek', 'newStartTime', 'newEndTime']);
        $this->showAddSlot = false;
        session()->flash('success', 'Slot aggiunto.');
    }

    public function deleteSlot(int $id): void
    {
        $this->assertCanEdit();

        OpeningHour::recurring()->findOrFail($id)->delete();

        session()->flash('success', 'Slot eliminato.');
    }

    // -------------------------------------------------------------------------
    // Slot ricorrenti — modifica inline
    // -------------------------------------------------------------------------

    public function startEditSlot(int $id): void
    {
        $this->assertCanEdit();

        $slot = OpeningHour::recurring()->findOrFail($id);

        $this->editingSlotId = $id;
        $this->editSlotDay = $slot->day_of_week;
        $this->editSlotStart = substr($slot->start_time, 0, 5);
        $this->editSlotEnd = substr($slot->end_time, 0, 5);

        // Chiude form aggiunta se aperto
        $this->showAddSlot = false;
    }

    public function saveSlot(): void
    {
        $this->assertCanEdit();

        $this->validate([
            'editSlotDay' => 'required|integer|min:0|max:6',
            'editSlotStart' => 'required|date_format:H:i',
            'editSlotEnd' => 'required|date_format:H:i|after:editSlotStart',
        ]);

        OpeningHour::recurring()->findOrFail($this->editingSlotId)->update([
            'day_of_week' => $this->editSlotDay,
            'start_time' => $this->editSlotStart,
            'end_time' => $this->editSlotEnd,
        ]);

        $this->cancelEditSlot();
        session()->flash('success', 'Slot aggiornato.');
    }

    public function cancelEditSlot(): void
    {
        $this->reset(['editingSlotId', 'editSlotDay', 'editSlotStart', 'editSlotEnd']);
    }

    // -------------------------------------------------------------------------
    // Eccezioni / festività — aggiunta
    // -------------------------------------------------------------------------

    public function addOverride(): void
    {
        $this->assertCanEdit();

        $rules = $this->overrideRules('new');
        $this->validate($rules);

        OpeningHour::create([
            'day_of_week' => null,
            'specific_date' => $this->newDate,
            'is_annual' => $this->newIsAnnual,
            'start_time' => $this->newIsOpen ? $this->newOverrideStart : null,
            'end_time' => $this->newIsOpen ? $this->newOverrideEnd : null,
            'is_open' => $this->newIsOpen,
            'notes' => $this->newNotes ?: null,
        ]);

        $this->reset(['newDate', 'newOverrideStart', 'newOverrideEnd', 'newIsOpen', 'newIsAnnual', 'newNotes']);
        $this->showAddOverride = false;
        session()->flash('success', 'Eccezione aggiunta.');
    }

    public function deleteOverride(int $id): void
    {
        $this->assertCanEdit();

        OpeningHour::overrides()->findOrFail($id)->delete();

        session()->flash('success', 'Eccezione eliminata.');
    }

    // -------------------------------------------------------------------------
    // Eccezioni / festività — modifica inline
    // -------------------------------------------------------------------------

    public function startEditOverride(int $id): void
    {
        $this->assertCanEdit();

        $override = OpeningHour::overrides()->findOrFail($id);

        $this->editingOverrideId = $id;
        $this->editOverrideDate = $override->specific_date->format('Y-m-d');
        $this->editOverrideStart = $override->start_time ? substr($override->start_time, 0, 5) : '';
        $this->editOverrideEnd = $override->end_time ? substr($override->end_time, 0, 5) : '';
        $this->editOverrideIsOpen = $override->is_open;
        $this->editOverrideIsAnnual = $override->is_annual;
        $this->editOverrideNotes = $override->notes ?? '';

        $this->showAddOverride = false;
    }

    public function saveOverride(): void
    {
        $this->assertCanEdit();

        $rules = $this->overrideRules('edit');
        $this->validate($rules);

        OpeningHour::overrides()->findOrFail($this->editingOverrideId)->update([
            'specific_date' => $this->editOverrideDate,
            'is_annual' => $this->editOverrideIsAnnual,
            'start_time' => $this->editOverrideIsOpen ? $this->editOverrideStart : null,
            'end_time' => $this->editOverrideIsOpen ? $this->editOverrideEnd : null,
            'is_open' => $this->editOverrideIsOpen,
            'notes' => $this->editOverrideNotes ?: null,
        ]);

        $this->cancelEditOverride();
        session()->flash('success', 'Eccezione aggiornata.');
    }

    public function cancelEditOverride(): void
    {
        $this->reset([
            'editingOverrideId', 'editOverrideDate', 'editOverrideStart',
            'editOverrideEnd', 'editOverrideIsOpen', 'editOverrideIsAnnual', 'editOverrideNotes',
        ]);
    }

    // -------------------------------------------------------------------------

    /** @return array<string, string> */
    private function overrideRules(string $prefix): array
    {
        $isAnnual = $prefix === 'new' ? $this->newIsAnnual : $this->editOverrideIsAnnual;
        $isOpen = $prefix === 'new' ? $this->newIsOpen : $this->editOverrideIsOpen;

        $dateField = $prefix === 'new' ? 'newDate' : 'editOverrideDate';
        $isOpenField = $prefix === 'new' ? 'newIsOpen' : 'editOverrideIsOpen';
        $isAnnualField = $prefix === 'new' ? 'newIsAnnual' : 'editOverrideIsAnnual';
        $notesField = $prefix === 'new' ? 'newNotes' : 'editOverrideNotes';
        $startField = $prefix === 'new' ? 'newOverrideStart' : 'editOverrideStart';
        $endField = $prefix === 'new' ? 'newOverrideEnd' : 'editOverrideEnd';

        $rules = [
            $dateField => $isAnnual ? 'required|date_format:Y-m-d' : 'required|date',
            $isOpenField => 'boolean',
            $isAnnualField => 'boolean',
            $notesField => 'nullable|string|max:255',
        ];

        if ($isOpen) {
            $rules[$startField] = 'required|date_format:H:i';
            $rules[$endField] = "required|date_format:H:i|after:{$startField}";
        }

        return $rules;
    }

    private function assertCanEdit(): void
    {
        abort_unless(Auth::user()->hasAnyRole(['gestore', 'receptionist']), 403);
    }
}
