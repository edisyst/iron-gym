<?php

namespace App\Livewire\Athlete;

use App\Models\Exercise;
use App\Models\ExerciseSet;
use App\Models\SessionExercise;
use App\Models\SessionReadinessCheck;
use App\Models\TrainingSession;
use App\Services\ExerciseSubstitutionFinder;
use App\Services\PersonalRecordDetector;
use App\Services\PlateLoadoutCalculator;
use App\Services\ReadinessEvaluator;
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

    public ?int $substitutingSeId = null;

    /**
     * Candidati sostituzione serializzati per Livewire.
     * Ogni elemento: id, slug, name_it, mechanic, skill_level, overlap, equipment_slugs[], primary_muscles[]
     *
     * @var array<int, array{id: int, slug: string, name_it: string, mechanic: string, skill_level: string, overlap: int, equipment_slugs: list<string>, primary_muscles: list<string>}>
     */
    public array $substitutionCandidates = [];

    public ?int $exerciseHistoryId = null;

    public string $exerciseHistoryName = '';

    public ?int $exerciseDetailId = null;

    public bool $showReadinessModal = false;

    public bool $showModulationProposal = false;

    /**
     * Proposta di modulazione carichi da mostrare all'atleta prima di avviare la sessione.
     * Struttura: score, outcome, suggestion, includesJointAlert, reduction_pct,
     *             sets[]{set_id, exercise_name, set_index, original_weight, proposed_weight},
     *             sets_to_remove[]{set_id, exercise_name, set_index}
     *
     * @var array<string, mixed>
     */
    public array $modulationProposal = [];

    public ?int $plateModalSetId = null;

    public float $plateBarWeight = 20.0;

    /**
     * Risultato del calcolo dischi per lato
     *
     * @var array{plates: array<array{weight_kg: float, count: int, color: string|null}>, loaded_kg: float, delta_kg: float, bar_kg: float, target_kg: float}|null
     */
    public ?array $plateLoadout = null;

    public int $currentGroupIndex = 0;

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
            $hasCheck = SessionReadinessCheck::where('training_session_id', $session->id)->exists();
            if ($hasCheck) {
                $this->startSession();
            } else {
                $this->showReadinessModal = true;
            }
        }

        foreach ($this->session->sessionExercises as $exercise) {
            foreach ($exercise->sets as $set) {
                $this->setData[$set->id] = [
                    'reps' => $set->actual_reps !== null
                        ? (string) $set->actual_reps
                        : ($set->planned_reps !== null ? (string) $set->planned_reps : ''),
                    'weight' => $set->actual_weight_kg !== null
                        ? (string) $set->actual_weight_kg
                        : ($set->planned_weight_kg !== null ? (string) $set->planned_weight_kg : ''),
                    'rir' => $set->actual_rir !== null
                        ? (string) $set->actual_rir
                        : ($set->planned_rir !== null ? (string) $set->planned_rir : ''),
                    'duration' => $set->actual_duration_sec !== null
                        ? (string) $set->actual_duration_sec
                        : ($set->planned_duration_sec !== null ? (string) $set->planned_duration_sec : ''),
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
        $set->refresh();

        $pr = app(PersonalRecordDetector::class)->check($set, auth()->id());
        if ($pr !== null) {
            $this->dispatch('pr-achieved',
                exerciseName: $set->sessionExercise->exercise->name_it,
                e1rm: $pr->value
            );
        }

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
        $set->refresh();

        $pr = app(PersonalRecordDetector::class)->check($set, auth()->id());
        if ($pr !== null) {
            $this->dispatch('pr-achieved',
                exerciseName: $set->sessionExercise->exercise->name_it,
                e1rm: $pr->value
            );
        }

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

    /**
     * Salta il check di readiness e avvia la sessione senza modulazione.
     */
    public function skipReadiness(): void
    {
        $this->showReadinessModal = false;
        $this->startSession();
    }

    /**
     * Salva il check di readiness, calcola la proposta e — se necessaria — la mostra.
     *
     * @param  int  $sleep  sleep_quality  0-3
     * @param  int  $stress  stress_level   0-3
     * @param  int  $soreness  soreness_level 0-3
     * @param  int  $joint  joint_status   0-3
     */
    public function submitReadiness(int $sleep, int $stress, int $soreness, int $joint, string $note = ''): void
    {
        foreach ([$sleep, $stress, $soreness, $joint] as $val) {
            if ($val < 0 || $val > 3) {
                return;
            }
        }

        $check = SessionReadinessCheck::create([
            'training_session_id' => $this->session->id,
            'sleep_quality' => $sleep,
            'stress_level' => $stress,
            'soreness_level' => $soreness,
            'joint_status' => $joint,
            'note' => $note !== '' ? $note : null,
        ]);

        $proposal = app(ReadinessEvaluator::class)->evaluate($check);

        $noteText = "Readiness pre-sessione: score {$proposal->score}/12.";
        if ($proposal->requiresModulation()) {
            $noteText .= " {$proposal->suggestion}";
        }

        $this->session->update(['trainer_notes' => $noteText]);
        $this->showReadinessModal = false;

        if (! $proposal->requiresModulation()) {
            $this->startSession();

            return;
        }

        $reductionPct = $proposal->outcome === 'reduce_5pct'
            ? (int) config('readiness.reduction_pct.medium', 5)
            : (int) config('readiness.reduction_pct.low', 10);

        $evaluator = app(ReadinessEvaluator::class);
        $sets = [];

        foreach ($this->session->sessionExercises as $se) {
            foreach ($se->sets->where('is_warmup', false)->whereNull('completed_at') as $set) {
                if ($set->planned_weight_kg !== null) {
                    $sets[] = [
                        'set_id' => $set->id,
                        'exercise_name' => $se->exercise->name_it,
                        'set_index' => $set->set_index,
                        'original_weight' => (float) $set->planned_weight_kg,
                        'proposed_weight' => $evaluator->applyReduction((float) $set->planned_weight_kg, $reductionPct),
                    ];
                }
            }
        }

        $setsToRemove = [];

        if ($proposal->outcome === 'reduce_10pct') {
            $minSets = (int) config('readiness.min_sets_for_removal', 3);

            foreach ($this->session->sessionExercises as $se) {
                $incomplete = $se->sets
                    ->where('is_warmup', false)
                    ->filter(fn ($s) => $s->completed_at === null)
                    ->sortByDesc('set_index');

                if ($incomplete->count() >= $minSets) {
                    $last = $incomplete->first();
                    $setsToRemove[] = [
                        'set_id' => $last->id,
                        'exercise_name' => $se->exercise->name_it,
                        'set_index' => $last->set_index,
                    ];
                }
            }
        }

        $this->modulationProposal = [
            'score' => $proposal->score,
            'outcome' => $proposal->outcome,
            'suggestion' => $proposal->suggestion,
            'includesJointAlert' => $proposal->includesJointAlert,
            'reduction_pct' => $reductionPct,
            'sets' => $sets,
            'sets_to_remove' => $setsToRemove,
        ];

        $this->showModulationProposal = true;
    }

    /**
     * Applica la modulazione proposta: aggiorna planned_weight_kg e rimuove i set extra.
     */
    public function acceptModulation(): void
    {
        foreach ($this->modulationProposal['sets'] ?? [] as $item) {
            ExerciseSet::whereHas('sessionExercise', fn ($q) => $q->where('session_id', $this->session->id))
                ->where('id', $item['set_id'])
                ->update(['planned_weight_kg' => $item['proposed_weight']]);
        }

        foreach ($this->modulationProposal['sets_to_remove'] ?? [] as $item) {
            $set = ExerciseSet::whereHas('sessionExercise', fn ($q) => $q->where('session_id', $this->session->id))
                ->where('id', $item['set_id'])
                ->first();

            if ($set) {
                $set->delete();
                unset($this->setData[$set->id]);
            }
        }

        $this->reloadSets();
        $this->showModulationProposal = false;
        $this->modulationProposal = [];
        $this->startSession();
    }

    /**
     * Rifiuta la modulazione: avvia la sessione senza modificare i carichi.
     */
    public function rejectModulation(): void
    {
        $this->showModulationProposal = false;
        $this->modulationProposal = [];
        $this->startSession();
    }

    private function startSession(): void
    {
        $this->session->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
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
     * Apre la modale di sostituzione per un session_exercise.
     * Bloccato se almeno un set working è già completato.
     */
    public function openSubstitutionModal(int $seId): void
    {
        $se = SessionExercise::where('session_id', $this->session->id)
            ->with(['exercise', 'sets'])
            ->findOrFail($seId);

        $hasCompleted = $se->sets
            ->where('is_warmup', false)
            ->whereNotNull('completed_at')
            ->isNotEmpty();

        if ($hasCompleted) {
            return;
        }

        $this->substitutingSeId = $seId;

        $candidates = app(ExerciseSubstitutionFinder::class)->find($se->exercise);

        $this->substitutionCandidates = $candidates->map(fn ($c) => [
            'id' => $c['exercise']->id,
            'slug' => $c['exercise']->slug,
            'name_it' => $c['exercise']->name_it,
            'mechanic' => $c['exercise']->mechanic,
            'skill_level' => $c['exercise']->skill_level,
            'overlap' => $c['overlap'],
            'equipment_slugs' => $c['equipment_slugs'],
            'primary_muscles' => $c['primary_muscles'],
        ])->values()->all();

        $this->dispatch('open-substitution-modal');
    }

    public function closeSubstitutionModal(): void
    {
        $this->substitutingSeId = null;
        $this->substitutionCandidates = [];
    }

    /**
     * Applica la sostituzione: aggiorna exercise_id e registra l'originale.
     * Nessun set viene toccato.
     */
    public function confirmSubstitution(string $newExerciseSlug): void
    {
        if ($this->substitutingSeId === null) {
            return;
        }

        $se = SessionExercise::where('session_id', $this->session->id)
            ->with(['sets'])
            ->findOrFail($this->substitutingSeId);

        $hasCompleted = $se->sets
            ->where('is_warmup', false)
            ->whereNotNull('completed_at')
            ->isNotEmpty();

        if ($hasCompleted) {
            $this->closeSubstitutionModal();

            return;
        }

        $newExercise = Exercise::where('slug', $newExerciseSlug)->firstOrFail();

        $se->update([
            'substituted_from_exercise_id' => $se->exercise_id,
            'exercise_id' => $newExercise->id,
        ]);

        $this->closeSubstitutionModal();

        $this->session->load([
            'sessionExercises' => fn ($q) => $q->orderBy('order_in_session'),
            'sessionExercises.exercise',
            'sessionExercises.exercise.equipment',
            'sessionExercises.sets' => fn ($q) => $q->orderBy('set_index'),
            'sessionExercises.group',
        ]);
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

    /** @return list<Collection<int, SessionExercise>> */
    protected function buildGroupedExercises(): array
    {
        return $this->session->sessionExercises
            ->sortBy('order_in_session')
            ->groupBy(fn ($e) => $e->group_id !== null ? 'group_'.$e->group_id : 'solo_'.$e->id)
            ->values()
            ->all();
    }

    public function nextGroup(): void
    {
        $count = count($this->buildGroupedExercises());
        if ($this->currentGroupIndex < $count - 1) {
            $this->currentGroupIndex++;
        }
    }

    public function prevGroup(): void
    {
        if ($this->currentGroupIndex > 0) {
            $this->currentGroupIndex--;
        }
    }

    public function jumpToGroup(int $index): void
    {
        $count = count($this->buildGroupedExercises());
        if ($index >= 0 && $index < $count) {
            $this->currentGroupIndex = $index;
        }
    }

    public function render(): View
    {
        $groupedExercises = $this->buildGroupedExercises();
        $totalGroups = count($groupedExercises);

        if ($this->currentGroupIndex >= $totalGroups && $totalGroups > 0) {
            $this->currentGroupIndex = $totalGroups - 1;
        }

        $currentGroup = collect($groupedExercises[$this->currentGroupIndex] ?? []);

        $totalSets = 0;
        $completedSets = 0;
        foreach ($this->session->sessionExercises as $se) {
            foreach ($se->sets->where('is_warmup', false) as $set) {
                $totalSets++;
                if ($set->completed_at !== null) {
                    $completedSets++;
                }
            }
        }

        return view('livewire.athlete.workout-session', [
            'groupedExercises' => $groupedExercises,
            'totalGroups' => $totalGroups,
            'currentGroup' => $currentGroup,
            'totalSets' => $totalSets,
            'completedSets' => $completedSets,
        ])->layout('layouts.athlete');
    }
}
