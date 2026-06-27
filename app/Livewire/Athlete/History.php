<?php

namespace App\Livewire\Athlete;

use App\Models\Exercise;
use App\Models\Mesocycle;
use App\Models\SessionExercise;
use App\Models\TrainingSession;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Storico allenamenti')]
class History extends Component
{
    use WithPagination;

    public string $mesocycleId = '';

    /** ID sessione espansa nel pannello dettaglio */
    public ?int $selectedSessionId = null;

    public ?int $exerciseHistoryId = null;

    public string $exerciseHistoryName = '';

    public ?int $exerciseDetailId = null;

    public function updatingMesocycleId(): void
    {
        $this->resetPage();
        $this->selectedSessionId = null;
    }

    public function showExerciseDetail(int $exerciseId): void
    {
        $this->exerciseDetailId = ($this->exerciseDetailId === $exerciseId) ? null : $exerciseId;
    }

    public function getExerciseDetailProperty(): ?Exercise
    {
        if ($this->exerciseDetailId === null) {
            return null;
        }

        return Exercise::with(['muscles', 'equipment', 'compoundPattern', 'jointAction'])
            ->find($this->exerciseDetailId);
    }

    public function showExerciseHistory(int $exerciseId, string $name): void
    {
        if ($this->exerciseHistoryId === $exerciseId) {
            $this->exerciseHistoryId = null;
            $this->exerciseHistoryName = '';

            return;
        }

        $this->exerciseHistoryId = $exerciseId;
        $this->exerciseHistoryName = $name;
    }

    /** @return Collection<int, SessionExercise> */
    public function getExerciseHistoryProperty(): Collection
    {
        if ($this->exerciseHistoryId === null) {
            return collect();
        }

        return SessionExercise::where('exercise_id', $this->exerciseHistoryId)
            ->whereHas('session', fn ($q) => $q
                ->where('status', 'completed')
                ->whereHas('week.mesocycle', fn ($q2) => $q2->where('athlete_id', auth()->id())))
            ->with([
                'session',
                'sets' => fn ($q) => $q->orderBy('set_index'),
            ])
            ->join('training_sessions', 'training_sessions.id', '=', 'session_exercises.session_id')
            ->orderByDesc('training_sessions.completed_at')
            ->select('session_exercises.*')
            ->get();
    }

    /**
     * Espande / collassa il pannello dettaglio di una sessione
     */
    public function showDetail(int $sessionId): void
    {
        if ($this->selectedSessionId === $sessionId) {
            $this->selectedSessionId = null;

            return;
        }

        $this->selectedSessionId = $sessionId;
    }

    /**
     * Carica la sessione selezionata con tutti i dati per il pannello dettaglio
     */
    public function getSelectedSessionProperty(): ?TrainingSession
    {
        if ($this->selectedSessionId === null) {
            return null;
        }

        return TrainingSession::whereHas(
            'week.mesocycle',
            fn ($q) => $q->where('athlete_id', auth()->id())
        )
            ->with([
                'feedback',
                'sessionExercises' => fn ($q) => $q->orderBy('order_in_session'),
                'sessionExercises.exercise',
                'sessionExercises.sets' => fn ($q) => $q->orderBy('set_index'),
            ])
            ->find($this->selectedSessionId);
    }

    /**
     * Formatta la durata sessione in minuti
     */
    public function duration(TrainingSession $session): ?string
    {
        if ($session->started_at === null || $session->completed_at === null) {
            return null;
        }

        $minutes = (int) $session->started_at->diffInMinutes($session->completed_at);

        return $minutes.' min';
    }

    /**
     * Conteggio set completati in una sessione (eager-loaded)
     */
    public function completedSetsCount(TrainingSession $session): int
    {
        return $session->sessionExercises->sum(
            fn ($e) => $e->sets->whereNotNull('completed_at')->count()
        );
    }

    public function render(): View
    {
        // Tutti i mesocicli dell'atleta per il select filtro
        $mesocycles = Mesocycle::where('athlete_id', auth()->id())
            ->orderByDesc('start_date')
            ->get();

        // Query sessioni completate
        $sessions = TrainingSession::whereHas(
            'week.mesocycle',
            fn ($q) => $q->where('athlete_id', auth()->id())
                ->when($this->mesocycleId !== '', fn ($q2) => $q2->where('id', $this->mesocycleId))
        )
            ->where('status', 'completed')
            ->with([
                'week.mesocycle',
                'sessionExercises.sets',
            ])
            ->orderByDesc('completed_at')
            ->paginate(20);

        return view('livewire.athlete.history', [
            'mesocycles' => $mesocycles,
            'sessions' => $sessions,
        ])->layout('layouts.athlete');
    }
}
