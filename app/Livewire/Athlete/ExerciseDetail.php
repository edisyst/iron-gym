<?php

namespace App\Livewire\Athlete;

use App\Models\Exercise;
use Illuminate\View\View;
use Livewire\Component;

class ExerciseDetail extends Component
{
    public Exercise $exercise;

    public function mount(Exercise $exercise): void
    {
        $this->exercise = $exercise->load([
            'muscles',
            'equipment',
            'compoundPattern',
            'jointAction',
        ]);
    }

    public function render(): View
    {
        return view('livewire.athlete.exercise-detail')
            ->layout('layouts.athlete');
    }
}
