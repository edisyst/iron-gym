<?php

namespace App\Livewire\Athlete;

use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\TrainingSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Il mio allenamento')]
class Dashboard extends Component
{
    public ?Mesocycle $activeMesocycle = null;

    public ?MicrocycleWeek $currentWeek = null;

    /** @var Collection<int, TrainingSession> */
    public Collection $weekSessions;

    public ?TrainingSession $nextSession = null;

    public ?TrainingSession $lastSession = null;

    public float $lastTonnage = 0;

    public int $lastSetsCompleted = 0;

    public function mount(): void
    {
        $this->weekSessions = collect();

        // Cerca il mesociclo attivo dell'atleta con le settimane e sessioni
        $this->activeMesocycle = Mesocycle::where('athlete_id', auth()->id())
            ->where('status', 'active')
            ->with([
                'weeks' => fn ($q) => $q->orderBy('week_number'),
                'weeks.sessions' => fn ($q) => $q->orderBy('order_in_week'),
            ])
            ->latest()
            ->first();

        if ($this->activeMesocycle === null) {
            return;
        }

        $today = Carbon::today();

        // Trova la settimana corrente in base alle date
        foreach ($this->activeMesocycle->weeks as $week) {
            if ($today->between($week->start_date, $week->end_date)) {
                $this->currentWeek = $week;
                break;
            }
        }

        // Se non siamo nel range di date del mesociclo, prendi la prima settimana
        // con sessioni non ancora completate
        if ($this->currentWeek === null) {
            foreach ($this->activeMesocycle->weeks as $week) {
                $hasIncomplete = $week->sessions->contains(
                    fn (TrainingSession $s) => $s->status !== 'completed'
                );
                if ($hasIncomplete) {
                    $this->currentWeek = $week;
                    break;
                }
            }

            // Fallback: prima settimana
            if ($this->currentWeek === null) {
                $this->currentWeek = $this->activeMesocycle->weeks->first();
            }
        }

        if ($this->currentWeek !== null) {
            $this->weekSessions = $this->currentWeek->sessions->sortBy('order_in_week')->values();
        }

        // Prossima sessione planned o in_progress del mesociclo attivo
        $this->nextSession = TrainingSession::whereHas(
            'week.mesocycle',
            fn ($q) => $q->where('id', $this->activeMesocycle->id)
        )->whereIn('status', ['planned', 'in_progress'])
            ->with([
                'sessionExercises' => fn ($q) => $q->orderBy('order_in_session')->limit(5),
                'sessionExercises.exercise',
                'week',
            ])
            ->orderBy('scheduled_date')
            ->first();

        // Ultima sessione completata (qualunque mesociclo)
        $this->lastSession = TrainingSession::whereHas(
            'week.mesocycle',
            fn ($q) => $q->where('athlete_id', auth()->id())
        )->where('status', 'completed')
            ->orderByDesc('completed_at')
            ->first();

        if ($this->lastSession !== null) {
            $sessionId = $this->lastSession->id;

            $this->lastTonnage = (float) DB::table('exercise_sets as es')
                ->join('session_exercises as se', 'se.id', '=', 'es.session_exercise_id')
                ->where('se.session_id', $sessionId)
                ->where('es.is_warmup', false)
                ->whereNotNull('es.completed_at')
                ->whereNotNull('es.actual_weight_kg')
                ->whereNotNull('es.actual_reps')
                ->sum(DB::raw('es.actual_weight_kg * es.actual_reps'));

            $this->lastSetsCompleted = DB::table('exercise_sets as es')
                ->join('session_exercises as se', 'se.id', '=', 'es.session_exercise_id')
                ->where('se.session_id', $sessionId)
                ->where('es.is_warmup', false)
                ->whereNotNull('es.completed_at')
                ->count();
        }
    }

    /**
     * Icona e classe CSS per lo status di una sessione
     */
    public function sessionStatusClass(string $status): string
    {
        return match ($status) {
            'planned' => 'status-planned',
            'in_progress' => 'status-in_progress',
            'completed' => 'status-completed',
            'skipped' => 'status-skipped',
            default => 'status-planned',
        };
    }

    /**
     * Label italiana per lo status sessione
     */
    public function sessionStatusLabel(string $status): string
    {
        return match ($status) {
            'planned' => 'Pianificata',
            'in_progress' => 'In corso',
            'completed' => 'Completata',
            'skipped' => 'Saltata',
            default => $status,
        };
    }

    /**
     * Label italiana per l'obiettivo
     */
    public function goalLabel(string $goal): string
    {
        return match ($goal) {
            'hypertrophy' => 'Ipertrofia',
            'strength' => 'Forza',
            'cut' => 'Definizione',
            'recomp' => 'Ricomposizione',
            'peaking' => 'Peaking',
            'general' => 'Generale',
            default => $goal,
        };
    }

    public function restoreSession(int $sessionId): void
    {
        TrainingSession::whereHas(
            'week.mesocycle', fn ($q) => $q->where('athlete_id', auth()->id())
        )->where('status', 'skipped')->findOrFail($sessionId)->update(['status' => 'planned']);

        $this->mount();
    }

    public function render(): View
    {
        return view('livewire.athlete.dashboard')
            ->layout('layouts.athlete');
    }
}
