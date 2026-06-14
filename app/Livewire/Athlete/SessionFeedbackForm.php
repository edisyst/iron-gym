<?php

namespace App\Livewire\Athlete;

use App\Models\SessionFeedback;
use App\Models\TrainingSession;
use Illuminate\View\View;
use Livewire\Component;

class SessionFeedbackForm extends Component
{
    public int $sessionId;

    // Metriche 0-3
    public ?int $pump = null;

    public ?int $sorenessPrev = null;

    public ?int $perceivedEffort = null;

    public ?int $jointPain = null;

    public ?int $performance = null;

    // Dati aggiuntivi
    public ?float $sleepHours = null;

    public ?int $stressLevel = null;

    public string $note = '';

    public function mount(TrainingSession $session): void
    {
        $this->sessionId = $session->id;
    }

    /**
     * Salva il feedback e torna alla dashboard
     */
    public function save(): void
    {
        $this->validate([
            'pump' => 'nullable|integer|between:0,3',
            'sorenessPrev' => 'nullable|integer|between:0,3',
            'perceivedEffort' => 'nullable|integer|between:0,3',
            'jointPain' => 'nullable|integer|between:0,3',
            'performance' => 'nullable|integer|between:0,3',
            'sleepHours' => 'nullable|numeric|between:0,24',
            'stressLevel' => 'nullable|integer|between:0,3',
            'note' => 'nullable|string|max:1000',
        ]);

        // Evita duplicati (unique su session_id)
        SessionFeedback::updateOrCreate(
            ['session_id' => $this->sessionId],
            [
                'pump' => $this->pump,
                'soreness_prev' => $this->sorenessPrev,
                'perceived_effort' => $this->perceivedEffort,
                'joint_pain' => $this->jointPain,
                'performance' => $this->performance,
                'sleep_hours' => $this->sleepHours,
                'stress_level' => $this->stressLevel,
                'note' => $this->note ?: null,
            ]
        );

        $this->dispatch('session-completed');
        $this->redirect(route('athlete.dashboard'), navigate: true);
    }

    /**
     * Salta il feedback e torna alla dashboard senza creare il record
     */
    public function skip(): void
    {
        $this->redirect(route('athlete.dashboard'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.athlete.session-feedback-form');
    }
}
