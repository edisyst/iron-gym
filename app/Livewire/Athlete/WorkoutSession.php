<?php

namespace App\Livewire\Athlete;

use App\Models\Exercise;
use App\Models\ExerciseSet;
use App\Models\SessionExercise;
use App\Models\TrainingSession;
use App\Services\PlateLoadoutCalculator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Allenamento')]
class WorkoutSession extends Component
{
    public TrainingSession $session;

    /** @var array<int, array{reps: string, weight: string, rir: string, duration: string}> */
    public array $setData = [];

    /**
     * Performance precedente per esercizio: [exercise_id][set_index] => [reps, weight, rir]
     *
     * @var array<int, array<int, array{reps: ?int, weight: ?float, rir: ?int}>>
     */
    public array $previousPerformance = [];

    public bool $showFeedback = false;

    public ?int $exerciseHistoryId = null;

    public string $exerciseHistoryName = '';

    public ?int $exerciseDetailId = null;

    public ?int $plateModalSetId = null;

    public float $plateBarWeight = 20.0;

    /**
     * Risultato del calcolo dischi per lato
     *
     * @var array{plates: array<array{weight_kg: float, count: int, color: string|null}>, loaded_kg: float, delta_kg: float, bar_kg: float, target_kg: float}|null
     */
    public ?array $plateLoadout = null;

    public function mount(TrainingSession $session): void
    {
        $session->load([
            'sessionExercises' => fn ($q) => $q->orderBy('order_in_session'),
            'sessionExercises.exercise',
            'sessionExercises.exercise.equipment',
            'sessionExercises.sets' => fn ($q) => $q->orderBy('set_index'),
            'sessionExercises.group',
            'week.mesocycle',
        ]);

        $this->plateBarWeight = (float) config('barbell.default_weight_kg', 20);

        if ($session->week->mesocycle->athlete_id !== auth()->id()) {
            abort(403, 'Non autorizzato.');
        }

        $this->session = $session;

        if ($this->session->status === 'planned') {
            $this->session->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }

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

        $this->loadPreviousPerformance();

        if (request()->query('feedback') == '1') {
            $this->showFeedback = true;
        }
    }

    /**
     * Carica, in un'unica query aggregata, l'ultima esecuzione completata
     * per ciascun esercizio presente nella sessione.
     */
    protected function loadPreviousPerformance(): void
    {
        $exerciseIds = $this->session->sessionExercises->pluck('exercise_id')->unique()->values();

        if ($exerciseIds->isEmpty()) {
            return;
        }

        $lastSes = SessionExercise::whereIn('exercise_id', $exerciseIds)
            ->join('training_sessions as ts', 'ts.id', '=', 'session_exercises.session_id')
            ->join('microcycle_weeks as mw', 'mw.id', '=', 'ts.microcycle_week_id')
            ->join('mesocycles as mc', 'mc.id', '=', 'mw.mesocycle_id')
            ->where('ts.status', 'completed')
            ->where('ts.id', '!=', $this->session->id)
            ->where('mc.athlete_id', auth()->id())
            ->orderByDesc('ts.completed_at')
            ->select('session_exercises.*')
            ->with(['sets' => fn ($q) => $q->where('is_warmup', false)->orderBy('set_index')])
            ->get()
            ->groupBy('exercise_id')
            ->map(fn ($group) => $group->first());

        $result = [];
        foreach ($lastSes as $exerciseId => $se) {
            foreach ($se->sets as $prevSet) {
                $result[$exerciseId][$prevSet->set_index] = [
                    'reps' => $prevSet->actual_reps,
                    'weight' => $prevSet->actual_weight_kg !== null ? (float) $prevSet->actual_weight_kg : null,
                    'rir' => $prevSet->actual_rir,
                ];
            }
        }

        $this->previousPerformance = $result;
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
     * Quick-log: copia planned_* in actual_* rispettando il measurement_type.
     * Non resetta completed_at se già valorizzato.
     */
    public function quickLog(int $setId): void
    {
        $set = ExerciseSet::whereHas('sessionExercise', fn ($q) => $q->where('session_id', $this->session->id))
            ->findOrFail($setId);

        $measurementType = $set->sessionExercise->exercise->measurement_type;

        $updates = [];

        match ($measurementType) {
            'reps_weight', 'time_weight' => $updates = [
                'actual_reps' => $set->planned_reps,
                'actual_weight_kg' => $set->planned_weight_kg,
                'actual_rir' => $set->planned_rir,
            ],
            'reps_only' => $updates = [
                'actual_reps' => $set->planned_reps,
                'actual_rir' => $set->planned_rir,
            ],
            'time', 'isometric_hold' => $updates = [
                'actual_duration_sec' => $set->planned_duration_sec,
            ],
            default => $updates = [],
        };

        if ($set->completed_at === null) {
            $updates['completed_at'] = now();
        }

        $set->update($updates);

        $this->setData[$setId] = [
            'reps' => $set->actual_reps !== null ? (string) $set->actual_reps : ($this->setData[$setId]['reps'] ?? ''),
            'weight' => $set->actual_weight_kg !== null ? (string) $set->actual_weight_kg : ($this->setData[$setId]['weight'] ?? ''),
            'rir' => $set->actual_rir !== null ? (string) $set->actual_rir : ($this->setData[$setId]['rir'] ?? ''),
            'duration' => $set->actual_duration_sec !== null ? (string) $set->actual_duration_sec : ($this->setData[$setId]['duration'] ?? ''),
        ];

        $this->reloadSets();
        $this->dispatch('set-completed', setId: $setId);
    }

    /**
     * Salva i valori actual digitati manualmente.
     * Non resetta completed_at se il set era già completato (es. dopo quick-log).
     */
    public function completeSet(int $setId): void
    {
        $set = ExerciseSet::whereHas('sessionExercise', fn ($q) => $q->where('session_id', $this->session->id))
            ->findOrFail($setId);

        $data = $this->setData[$setId] ?? [];

        $updates = [
            'actual_reps' => $data['reps'] !== '' ? (int) $data['reps'] : null,
            'actual_weight_kg' => $data['weight'] !== '' ? (float) $data['weight'] : null,
            'actual_rir' => $data['rir'] !== '' ? (int) $data['rir'] : null,
            'actual_duration_sec' => $data['duration'] !== '' ? (int) $data['duration'] : null,
        ];

        if ($set->completed_at === null) {
            $updates['completed_at'] = now();
        }

        $set->update($updates);

        $this->reloadSets();
        $this->dispatch('set-completed', setId: $setId);
    }

    /**
     * Genera set di riscaldamento prima dei working set.
     * Idempotente: se esistono già warm-up per questo session_exercise non fa nulla.
     */
    public function generateWarmup(int $sessionExerciseId): void
    {
        SessionExercise::where('session_id', $this->session->id)->findOrFail($sessionExerciseId);

        $existingWarmup = ExerciseSet::where('session_exercise_id', $sessionExerciseId)
            ->where('is_warmup', true)
            ->exists();

        if ($existingWarmup) {
            return;
        }

        $firstWorkingSet = ExerciseSet::where('session_exercise_id', $sessionExerciseId)
            ->where('is_warmup', false)
            ->orderBy('set_index')
            ->first();

        if (! $firstWorkingSet || $firstWorkingSet->planned_weight_kg === null) {
            return;
        }

        $target = (float) $firstWorkingSet->planned_weight_kg;

        $warmupDef = $target >= 40
            ? [[0.50, 8], [0.70, 5], [0.85, 3]]
            : [[0.50, 8]];

        $warmupCount = count($warmupDef);

        // Shifta i working set per fare spazio ai warm-up
        ExerciseSet::where('session_exercise_id', $sessionExerciseId)
            ->where('is_warmup', false)
            ->orderByDesc('set_index')
            ->get()
            ->each(fn ($s) => $s->update(['set_index' => $s->set_index + $warmupCount]));

        foreach ($warmupDef as $i => [$pct, $reps]) {
            $weight = round($target * $pct / 2.5) * 2.5;
            $newSet = ExerciseSet::create([
                'session_exercise_id' => $sessionExerciseId,
                'set_index' => $i + 1,
                'is_warmup' => true,
                'planned_reps' => $reps,
                'planned_weight_kg' => $weight,
                'planned_rir' => null,
                'planned_duration_sec' => null,
            ]);
            $this->setData[$newSet->id] = ['reps' => '', 'weight' => '', 'rir' => '', 'duration' => ''];
        }

        $this->reloadSets();
    }

    /**
     * Elimina un singolo set di riscaldamento.
     */
    public function deleteWarmupSet(int $setId): void
    {
        $set = ExerciseSet::whereHas('sessionExercise', fn ($q) => $q->where('session_id', $this->session->id))
            ->where('is_warmup', true)
            ->findOrFail($setId);

        $set->delete();
        unset($this->setData[$setId]);

        $this->reloadSets();
    }

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

    public function completeSession(): void
    {
        $this->session->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->showFeedback = true;
        $this->dispatch('open-feedback');
    }

    public function skipSession(): void
    {
        $this->session->update(['status' => 'skipped']);

        $this->redirect(route('athlete.dashboard'));
    }

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

    protected function reloadSets(): void
    {
        $this->session->load([
            'sessionExercises.sets' => fn ($q) => $q->orderBy('set_index'),
        ]);
    }

    /**
     * Apre la modale plate calculator per il set specificato.
     * Verifica ownership tramite session_id prima di procedere.
     */
    public function openPlateModal(int $setId): void
    {
        $set = ExerciseSet::whereHas('sessionExercise', fn ($q) => $q->where('session_id', $this->session->id))
            ->findOrFail($setId);

        $this->plateModalSetId = $setId;
        $this->recalculatePlates((float) $set->planned_weight_kg);
        $this->dispatch('open-plate-modal');
    }

    /**
     * Aggiorna il peso barra selezionato e ricalcola i dischi.
     */
    public function updatePlateBar(float $barWeight): void
    {
        $this->plateBarWeight = $barWeight;

        if ($this->plateModalSetId === null) {
            return;
        }

        $set = ExerciseSet::find($this->plateModalSetId);

        if ($set) {
            $this->recalculatePlates((float) $set->planned_weight_kg);
        }
    }

    /**
     * Calcola i dischi per lato e salva il risultato in $plateLoadout.
     */
    protected function recalculatePlates(float $targetKg): void
    {
        $calculator = app(PlateLoadoutCalculator::class);
        $this->plateLoadout = $calculator->calculate($targetKg, $this->plateBarWeight);
    }

    /**
     * Chiude la modale e azzera lo stato del plate calculator.
     */
    public function closePlateModal(): void
    {
        $this->plateModalSetId = null;
        $this->plateLoadout = null;
    }

    /**
     * Restituisce true se l'esercizio del SessionExercise dato usa bilanciere o smith machine.
     * Utilizza la collection gia' caricata in eager loading — nessuna query aggiuntiva.
     */
    public function exerciseUsesBarbell(int $sessionExerciseId): bool
    {
        $se = $this->session->sessionExercises->firstWhere('id', $sessionExerciseId);

        if (! $se) {
            return false;
        }

        return $se->exercise->equipment->contains(
            fn ($eq) => in_array($eq->slug, ['barbell', 'smith_machine'])
        );
    }

    public function render(): View
    {
        return view('livewire.athlete.workout-session')
            ->layout('layouts.athlete');
    }
}
