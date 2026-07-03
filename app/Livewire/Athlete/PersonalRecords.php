<?php

namespace App\Livewire\Athlete;

use App\Models\PersonalRecord;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Record personali')]
class PersonalRecords extends Component
{
    use WithPagination;

    public function render(): View
    {
        $records = PersonalRecord::with('exercise')
            ->where('athlete_id', auth()->id())
            ->where('record_type', 'e1rm')
            ->orderByDesc('achieved_at')
            ->paginate(20);

        return view('livewire.athlete.personal-records', compact('records'))
            ->layout('layouts.athlete');
    }
}
