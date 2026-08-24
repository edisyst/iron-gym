<?php

namespace App\Livewire\Backoffice\Calendar;

use App\Models\ClassOccurrence;
use App\Models\GroupClass;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;

class GroupClassCatalog extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $formName = '';

    public string $formDescription = '';

    public int $formDurationMinutes = 60;

    public int $formDefaultCapacity = 10;

    public string $formRoom = '';

    public string $formColor = '#E85D04';

    public bool $formIsActive = true;

    // -------------------------------------------------------------------------
    // Form
    // -------------------------------------------------------------------------

    public function openForm(?int $id = null): void
    {
        if ($id !== null) {
            $gc = GroupClass::findOrFail($id);
            $this->editingId = $gc->id;
            $this->formName = $gc->name;
            $this->formDescription = $gc->description ?? '';
            $this->formDurationMinutes = $gc->duration_minutes;
            $this->formDefaultCapacity = $gc->default_capacity;
            $this->formRoom = $gc->room ?? '';
            $this->formColor = $gc->color ?? '#E85D04';
            $this->formIsActive = (bool) $gc->is_active;
        } else {
            $this->editingId = null;
            $this->reset(['formName', 'formDescription', 'formRoom']);
            $this->formDurationMinutes = 60;
            $this->formDefaultCapacity = 10;
            $this->formColor = '#E85D04';
            $this->formIsActive = true;
        }

        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless(Auth::user()->hasRole('gestore'), 403);

        $this->validate([
            'formName' => 'required|string|max:128',
            'formDescription' => 'nullable|string',
            'formDurationMinutes' => 'required|integer|min:15|max:480',
            'formDefaultCapacity' => 'required|integer|min:1|max:200',
            'formRoom' => 'nullable|string|max:64',
            'formColor' => 'nullable|string|max:32',
        ], [
            'formName.required' => 'Il nome del corso è obbligatorio.',
            'formDurationMinutes.min' => 'Durata minima 15 minuti.',
            'formDefaultCapacity.min' => 'Capacità minima 1 partecipante.',
        ]);

        $data = [
            'name' => $this->formName,
            'description' => $this->formDescription ?: null,
            'duration_minutes' => $this->formDurationMinutes,
            'default_capacity' => $this->formDefaultCapacity,
            'room' => $this->formRoom ?: null,
            'color' => $this->formColor ?: null,
            'is_active' => $this->formIsActive,
        ];

        if ($this->editingId !== null) {
            $gc = GroupClass::findOrFail($this->editingId);
            $gc->update($data);
            session()->flash('success', 'Corso aggiornato.');
        } else {
            $data['slug'] = Str::slug($this->formName);

            // Garantisce slug univoco
            $base = $data['slug'];
            $i = 1;
            while (GroupClass::where('slug', $data['slug'])->exists()) {
                $data['slug'] = $base.'-'.$i++;
            }

            GroupClass::create($data);
            session()->flash('success', 'Corso creato.');
        }

        $this->showForm = false;
        $this->editingId = null;
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    // -------------------------------------------------------------------------
    // Azioni riga
    // -------------------------------------------------------------------------

    public function toggleActive(int $id): void
    {
        abort_unless(Auth::user()->hasRole('gestore'), 403);

        $gc = GroupClass::findOrFail($id);
        $gc->update(['is_active' => ! $gc->is_active]);
    }

    public function deleteClass(int $id): void
    {
        abort_unless(Auth::user()->hasRole('gestore'), 403);

        $hasFutureOccurrences = ClassOccurrence::where('group_class_id', $id)
            ->whereDate('date', '>=', today())
            ->where('status', 'planned')
            ->exists();

        if ($hasFutureOccurrences) {
            session()->flash('error', 'Impossibile eliminare: esistono occorrenze future pianificate.');

            return;
        }

        GroupClass::findOrFail($id)->delete();
        session()->flash('success', 'Corso eliminato.');
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render(): View
    {
        $classes = GroupClass::withCount(['occurrences as future_count' => fn ($q) => $q->whereDate('date', '>=', today())])
            ->orderBy('name')
            ->get();

        return view('livewire.backoffice.calendar.group-class-catalog', [
            'classes' => $classes,
        ])->layout('layouts.backoffice')->layoutData(['page_title' => 'Catalogo corsi']);
    }
}
