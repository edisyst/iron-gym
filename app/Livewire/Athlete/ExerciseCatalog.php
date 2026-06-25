<?php

namespace App\Livewire\Athlete;

use App\Models\Exercise;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ExerciseCatalog extends Component
{
    use WithPagination;

    public string $search = '';

    public string $mechanic = '';

    public string $muscleGroup = '';

    /** @var array<string, array<string, string>> */
    protected $queryString = [
        'search' => ['except' => ''],
        'mechanic' => ['except' => ''],
        'muscleGroup' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingMechanic(): void
    {
        $this->resetPage();
    }

    public function updatingMuscleGroup(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $exercises = Exercise::with([
            'muscles' => fn ($q) => $q->wherePivot('role', 'primary'),
        ])
            ->when($this->search, fn ($q) => $q->where('name_it', 'like', "%{$this->search}%"))
            ->when($this->mechanic, fn ($q) => $q->where('mechanic', $this->mechanic))
            ->when($this->muscleGroup, function ($q) {
                $q->whereHas('muscles', function ($q2) {
                    $q2->where('muscle_group', $this->muscleGroup)
                        ->where('exercise_muscle.role', 'primary');
                });
            })
            ->orderBy('name_it')
            ->paginate(24);

        return view('livewire.athlete.exercise-catalog', [
            'exercises' => $exercises,
        ])->layout('layouts.athlete');
    }
}
