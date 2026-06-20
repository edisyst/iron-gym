<?php

namespace App\Livewire\Backoffice\Calendar;

use App\Models\TrainerAvailability;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class AvailabilityManager extends Component
{
    // -------------------------------------------------------------------------
    // Form slot ricorrente
    // -------------------------------------------------------------------------

    public bool $showAddSlot = false;

    #[Validate('required|integer|min:0|max:6')]
    public int $newDayOfWeek = 0;

    #[Validate('required|date_format:H:i')]
    public string $newStartTime = '';

    #[Validate('required|date_format:H:i|after:newStartTime')]
    public string $newEndTime = '';

    // -------------------------------------------------------------------------
    // Form override puntuale
    // -------------------------------------------------------------------------

    public bool $showAddOverride = false;

    #[Validate('required|date|after_or_equal:today')]
    public string $newDate = '';

    #[Validate('required|date_format:H:i')]
    public string $newOverrideStart = '';

    #[Validate('required|date_format:H:i|after:newOverrideStart')]
    public string $newOverrideEnd = '';

    #[Validate('boolean')]
    public bool $newIsAvailable = false;

    #[Validate('nullable|string|max:255')]
    public string $newNotes = '';

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render(): View
    {
        $trainerId = (int) Auth::id();

        // Slot ricorrenti ordinati per giorno poi per orario
        $slots = TrainerAvailability::where('trainer_id', $trainerId)
            ->recurring()
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        // Override puntuali ordinati per data
        $overrides = TrainerAvailability::where('trainer_id', $trainerId)
            ->overrides()
            ->orderBy('specific_date')
            ->orderBy('start_time')
            ->get();

        // Mappa giorni settimana per la UI
        $daysOfWeek = [
            0 => 'Lunedì',
            1 => 'Martedì',
            2 => 'Mercoledì',
            3 => 'Giovedì',
            4 => 'Venerdì',
            5 => 'Sabato',
            6 => 'Domenica',
        ];

        return view('livewire.backoffice.calendar.availability-manager', compact(
            'slots', 'overrides', 'daysOfWeek'
        ))->layout('layouts.backoffice')->layoutData(['page_title' => 'Gestione disponibilità']);
    }

    // -------------------------------------------------------------------------
    // Azioni slot ricorrenti
    // -------------------------------------------------------------------------

    /**
     * Aggiunge uno slot ricorrente per il trainer autenticato.
     */
    public function addSlot(): void
    {
        $this->validateOnly('newDayOfWeek');
        $this->validateOnly('newStartTime');
        $this->validateOnly('newEndTime');

        TrainerAvailability::create([
            'trainer_id' => Auth::id(),
            'day_of_week' => $this->newDayOfWeek,
            'specific_date' => null,
            'start_time' => $this->newStartTime,
            'end_time' => $this->newEndTime,
            'is_available' => true,
        ]);

        $this->reset(['newDayOfWeek', 'newStartTime', 'newEndTime']);
        $this->showAddSlot = false;
        session()->flash('success', 'Slot ricorrente aggiunto.');
    }

    /**
     * Elimina uno slot ricorrente del trainer autenticato.
     */
    public function deleteSlot(int $id): void
    {
        TrainerAvailability::where('id', $id)
            ->where('trainer_id', Auth::id())
            ->whereNotNull('day_of_week')
            ->delete();

        session()->flash('success', 'Slot eliminato.');
    }

    // -------------------------------------------------------------------------
    // Azioni override puntuali
    // -------------------------------------------------------------------------

    /**
     * Aggiunge un override puntuale (apertura straordinaria o blocco).
     */
    public function addOverride(): void
    {
        $this->validateOnly('newDate');
        $this->validateOnly('newOverrideStart');
        $this->validateOnly('newOverrideEnd');

        TrainerAvailability::create([
            'trainer_id' => Auth::id(),
            'day_of_week' => null,
            'specific_date' => $this->newDate,
            'start_time' => $this->newOverrideStart,
            'end_time' => $this->newOverrideEnd,
            'is_available' => $this->newIsAvailable,
            'notes' => $this->newNotes ?: null,
        ]);

        $this->reset(['newDate', 'newOverrideStart', 'newOverrideEnd', 'newIsAvailable', 'newNotes']);
        $this->showAddOverride = false;
        session()->flash('success', 'Eccezione aggiunta.');
    }

    /**
     * Elimina un override puntuale del trainer autenticato.
     */
    public function deleteOverride(int $id): void
    {
        TrainerAvailability::where('id', $id)
            ->where('trainer_id', Auth::id())
            ->whereNotNull('specific_date')
            ->delete();

        session()->flash('success', 'Eccezione eliminata.');
    }
}
