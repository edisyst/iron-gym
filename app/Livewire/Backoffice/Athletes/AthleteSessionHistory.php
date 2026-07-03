<?php

namespace App\Livewire\Backoffice\Athletes;

use App\Models\Mesocycle;
use App\Models\SessionExercise;
use App\Models\TrainingSession;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class AthleteSessionHistory extends Component
{
    use WithPagination;

    public int $athleteId;

    public string $mesocycleId = '';

    public ?int $selectedSessionId = null;

    public ?int $exerciseHistoryId = null;

    public string $exerciseHistoryName = '';

    public function mount(int $athleteId): void
    {
        $this->athleteId = $athleteId;
    }

    public function updatingMesocycleId(): void
    {
        $this->resetPage();
        $this->selectedSessionId = null;
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
                ->whereHas('week.mesocycle', fn ($q2) => $q2->where('athlete_id', $this->athleteId)))
            ->with([
                'session',
                'sets' => fn ($q) => $q->orderBy('set_index'),
            ])
            ->join('training_sessions', 'training_sessions.id', '=', 'session_exercises.session_id')
            ->orderByDesc('training_sessions.completed_at')
            ->select('session_exercises.*')
            ->get();
    }

    public function showDetail(int $sessionId): void
    {
        // La query in getSelectedSessionProperty() verifica l'appartenenza all'atleta.
        // Se la sessione non appartiene a $this->athleteId, find() restituisce null.
        $this->selectedSessionId = ($this->selectedSessionId === $sessionId)
            ? null
            : $sessionId;
    }

    public function getSelectedSessionProperty(): ?TrainingSession
    {
        if ($this->selectedSessionId === null) {
            return null;
        }

        return TrainingSession::whereHas(
            'week.mesocycle',
            fn ($q) => $q->where('athlete_id', $this->athleteId)
        )
            ->with([
                'feedback',
                'sessionExercises' => fn ($q) => $q->orderBy('order_in_session'),
                'sessionExercises.exercise',
                'sessionExercises.substitutedFrom',
                'sessionExercises.sets' => fn ($q) => $q->orderBy('set_index'),
            ])
            ->find($this->selectedSessionId);
    }

    public function duration(TrainingSession $session): ?string
    {
        if ($session->started_at === null || $session->completed_at === null) {
            return null;
        }

        return (int) $session->started_at->diffInMinutes($session->completed_at).' min';
    }

    public function completedSetsCount(TrainingSession $session): int
    {
        return $session->sessionExercises->sum(
            fn ($e) => $e->sets->whereNotNull('completed_at')->count()
        );
    }

    public function totalSetsCount(TrainingSession $session): int
    {
        return $session->sessionExercises->sum(
            fn ($e) => $e->sets->count()
        );
    }

    public function render(): View
    {
        $mesocycles = Mesocycle::where('athlete_id', $this->athleteId)
            ->orderByDesc('start_date')
            ->get();

        $sessions = TrainingSession::whereHas(
            'week.mesocycle',
            fn ($q) => $q->where('athlete_id', $this->athleteId)
                ->when($this->mesocycleId !== '', fn ($q2) => $q2->where('id', $this->mesocycleId))
        )
            ->where('status', 'completed')
            ->with(['week.mesocycle.trainer', 'sessionExercises.sets', 'feedback'])
            ->orderByDesc('completed_at')
            ->paginate(20);

        return view('livewire.backoffice.athletes.athlete-session-history', [
            'mesocycles' => $mesocycles,
            'sessions' => $sessions,
        ]);
    }
}
