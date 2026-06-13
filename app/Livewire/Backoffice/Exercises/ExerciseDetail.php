<?php

namespace App\Livewire\Backoffice\Exercises;

use App\Models\Exercise;
use Illuminate\View\View;
use Livewire\Component;

class ExerciseDetail extends Component
{
    public Exercise $exercise;

    public function mount(Exercise $exercise): void
    {
        // Carica relazioni per evitare N+1 nella view
        $this->exercise = $exercise->load([
            'muscles',
            'equipment',
            'compoundPattern',
            'jointAction',
            'creator',
        ]);
    }

    public function render(): View
    {
        return view('livewire.backoffice.exercises.exercise-detail')
            ->layout('layouts.backoffice')
            ->layoutData(['page_title' => $this->exercise->name_it]);
    }
}
