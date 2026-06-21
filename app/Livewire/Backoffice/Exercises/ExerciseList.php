<?php

namespace App\Livewire\Backoffice\Exercises;

use App\Models\Equipment;
use App\Models\Exercise;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ExerciseList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $muscleGroup = '';

    public string $mechanic = '';

    public string $skillLevel = '';

    /** @var array<int> */
    public array $equipmentFilter = [];

    /** @var array<string, array<string, string>> */
    protected $queryString = [
        'search' => ['except' => ''],
        'muscleGroup' => ['except' => ''],
        'mechanic' => ['except' => ''],
        'skillLevel' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingMuscleGroup(): void
    {
        $this->resetPage();
    }

    public function updatingMechanic(): void
    {
        $this->resetPage();
    }

    public function updatingSkillLevel(): void
    {
        $this->resetPage();
    }

    public function updatingEquipmentFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $filters = [
            'search' => $this->search,
            'muscleGroup' => $this->muscleGroup,
            'mechanic' => $this->mechanic,
            'skillLevel' => $this->skillLevel,
            'equipment' => $this->equipmentFilter,
        ];

        $query = Exercise::with([
            'muscles' => fn ($q) => $q->wherePivot('role', 'primary'),
            'compoundPattern',
            'jointAction',
            'equipment',
        ])
            ->when($this->search, fn ($q) => $q->where('name_it', 'like', "%{$this->search}%"))
            ->when($this->muscleGroup, function ($q) {
                // Filtra esercizi che hanno un muscolo primary nel gruppo selezionato
                $q->whereHas('muscles', function ($q2) {
                    $q2->where('muscle_group', $this->muscleGroup)
                        ->where('exercise_muscle.role', 'primary');
                });
            })
            ->when($this->mechanic, fn ($q) => $q->where('mechanic', $this->mechanic))
            ->when($this->skillLevel, fn ($q) => $q->where('skill_level', $this->skillLevel))
            ->when($this->equipmentFilter, function ($q) {
                $q->whereHas('equipment', fn ($q2) => $q2->whereIn('equipment.id', $this->equipmentFilter));
            })
            ->orderBy('name_it');

        $allEquipment = Cache::remember('exercises:equipment', 86400, fn () => Equipment::orderBy('name_it')->get()->map(fn ($e) => ['id' => $e->id, 'name_it' => $e->name_it])->all());

        return view('livewire.backoffice.exercises.exercise-list', [
            'exercises' => $query->paginate(20),
            'allEquipment' => $allEquipment,
        ])->layout('layouts.backoffice')
            ->layoutData(['page_title' => 'Libreria esercizi']);
    }
}
