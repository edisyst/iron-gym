<?php

namespace App\Livewire\Athlete;

use App\Models\TrainingSession;
use App\Services\SessionRecapBuilder;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Riepilogo sessione')]
class SessionRecap extends Component
{
    public TrainingSession $session;

    /**
     * Dati riepilogo serializzabili per la view.
     * I PersonalRecord vengono appiattiti in array scalari (no model Eloquent nella proprietà pubblica).
     *
     * @var array<string, mixed>
     */
    public array $recap = [];

    public function mount(TrainingSession $session): void
    {
        $owns = TrainingSession::whereHas(
            'week.mesocycle',
            fn ($q) => $q->where('athlete_id', auth()->id())
        )->where('id', $session->id)->where('status', 'completed')->exists();

        abort_unless($owns, 403);

        $this->session = $session;

        $data = app(SessionRecapBuilder::class)->build($session, auth()->id());

        $this->recap = [
            'duration_minutes' => $data['duration_minutes'],
            'tonnage_kg' => $data['tonnage_kg'],
            'sets_completed' => $data['sets_completed'],
            'sets_prescribed' => $data['sets_prescribed'],
            'prs' => $data['prs']->map(fn ($pr) => [
                'exercise_name' => $pr->exercise->name_it,
                'value' => round($pr->value, 1),
            ])->values()->all(),
            'top_muscles' => $data['top_muscles'],
        ];
    }

    public function render(): View
    {
        return view('livewire.athlete.session-recap')
            ->layout('layouts.athlete');
    }
}
