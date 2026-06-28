<?php

namespace App\Livewire\Backoffice\Athletes;

use App\Models\Mesocycle;
use App\Models\User;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Profilo atleta')]
class AthleteProfile extends Component
{
    public int $athleteId;

    public function mount(int $athleteId): void
    {
        if (! auth()->user()?->hasRole('gestore')) {
            abort_unless(
                Mesocycle::where('athlete_id', $athleteId)
                    ->where('trainer_id', auth()->id())
                    ->exists(),
                403
            );
        }

        $this->athleteId = $athleteId;
    }

    public function render(): View
    {
        $athlete = User::with('member')->findOrFail($this->athleteId);

        $activeMesocycle = Mesocycle::where('athlete_id', $this->athleteId)
            ->where('status', 'active')
            ->first();

        $currentWeek = null;
        if ($activeMesocycle !== null) {
            $currentWeek = $activeMesocycle->weeks()
                ->where('start_date', '<=', now()->toDateString())
                ->where('end_date', '>=', now()->toDateString())
                ->first();
        }

        $athleteName = $athlete->member
            ? $athlete->member->first_name.' '.$athlete->member->last_name
            : $athlete->name;

        return view('livewire.backoffice.athletes.athlete-profile', [
            'athlete' => $athlete,
            'athleteName' => $athleteName,
            'activeMesocycle' => $activeMesocycle,
            'currentWeek' => $currentWeek,
        ])
            ->layout('layouts.backoffice')
            ->layoutData(['page_title' => 'Profilo atleta — '.$athleteName]);
    }
}
