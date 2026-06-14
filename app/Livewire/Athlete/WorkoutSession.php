<?php

namespace App\Livewire\Athlete;

use App\Models\ExerciseSet;
use App\Models\TrainingSession;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Allenamento')]
class WorkoutSession extends Component
{
    public TrainingSession $session;

    /** @var array<int, array{reps: string, weight: string, rir: string, duration: string}> */
    public array $setData = [];

    /** Mostra il form feedback dopo il completamento sessione */
    public bool $showFeedback = false;

    public function mount(TrainingSession $session): void
    {
        // Eager-load tutto il necessario per evitare N+1
        $session->load([
            'sessionExercises' => fn ($q) => $q->orderBy('order_in_session'),
            'sessionExercises.exercise',
            'sessionExercises.sets' => fn ($q) => $q->orderBy('set_index'),
            'sessionExercises.group',
            'week.mesocycle',
        ]);

        // Verifica che la sessione appartenga all'atleta autenticato
        if ($session->week->mesocycle->athlete_id !== auth()->id()) {
            abort(403, 'Non autorizzato.');
        }

        $this->session = $session;

        // Se la sessione è ancora planned, portala in in_progress
        if ($this->session->status === 'planned') {
            $this->session->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }

        // Inizializza $setData per ogni set caricato
        foreach ($this->session->sessionExercises as $exercise) {
            foreach ($exercise->sets as $set) {
                $this->setData[$set->id] = [
                    'reps' => $set->actual_reps !== null ? (string) $set->actual_reps : '',
                    'weight' => $set->actual_weight_kg !== null ? (string) $set->actual_weight_kg : '',
                    'rir' => $set->actual_rir !== null ? (string) $set->actual_rir : '',
                    'duration' => $set->actual_duration_sec !== null ? (string) $set->actual_duration_sec : '',
                ];
            }
        }

        // Se arriva con ?feedback=1 nella query, mostra subito il form feedback
        if (request()->query('feedback') == '1') {
            $this->showFeedback = true;
        }
    }

    /**
     * Completa un singolo set registrando i dati actual
     */
    public function completeSet(int $setId): void
    {
        // Verifica che il set appartenga a questa sessione
        $set = ExerciseSet::whereHas('sessionExercise', fn ($q) => $q->where('session_id', $this->session->id))
            ->findOrFail($setId);

        $data = $this->setData[$setId] ?? [];

        $set->update([
            'actual_reps' => $data['reps'] !== '' ? (int) $data['reps'] : null,
            'actual_weight_kg' => $data['weight'] !== '' ? (float) $data['weight'] : null,
            'actual_rir' => $data['rir'] !== '' ? (int) $data['rir'] : null,
            'actual_duration_sec' => $data['duration'] !== '' ? (int) $data['duration'] : null,
            'completed_at' => now(),
        ]);

        // Ricarica i set aggiornati
        $this->session->load([
            'sessionExercises.sets' => fn ($q) => $q->orderBy('set_index'),
        ]);

        $this->dispatch('set-completed', setId: $setId);
    }

    /**
     * Ritorna true se tutti i working set sono stati completati
     */
    public function canCompleteSession(): bool
    {
        foreach ($this->session->sessionExercises as $exercise) {
            foreach ($exercise->sets as $set) {
                if (! $set->is_warmup && $set->completed_at === null) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Completa la sessione e apre il form feedback
     */
    public function completeSession(): void
    {
        $this->session->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->showFeedback = true;
        $this->dispatch('open-feedback');
    }

    /**
     * Salta la sessione e torna alla dashboard
     */
    public function skipSession(): void
    {
        $this->session->update(['status' => 'skipped']);

        $this->redirect(route('athlete.dashboard'), navigate: true);
    }

    /**
     * Label italiana per la technique_type
     */
    public function techniqueLabel(string $type): string
    {
        return match ($type) {
            'straight' => 'Dritto',
            'drop_set' => 'Drop set',
            'rest_pause' => 'Rest-pause',
            'myo_reps' => 'Myo-reps',
            'cluster' => 'Cluster',
            'twenty_ones' => '21s',
            'pre_exhaustion' => 'Pre-esaurimento',
            'emom' => 'EMOM',
            'amrap' => 'AMRAP',
            default => $type,
        };
    }

    public function render(): View
    {
        return view('livewire.athlete.workout-session')
            ->layout('layouts.athlete');
    }
}
