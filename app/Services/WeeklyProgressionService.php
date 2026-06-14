<?php

namespace App\Services;

use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\SessionFeedback;
use App\Models\TrainingSession;
use App\ValueObjects\ProgressionResult;
use Illuminate\Support\Facades\DB;

class WeeklyProgressionService
{
    public function __construct(private WeeklyVolumeCalculator $volumeCalc) {}

    /**
     * Calcola il volume target per la settimana fromWeekNumber + 1 e aggiorna
     * i planned_sets_count degli esercizi nelle sessioni della settimana successiva.
     */
    public function progressWeek(int $mesocycleId, int $fromWeekNumber): ProgressionResult
    {
        $mesocycle = Mesocycle::with('weeks.sessions.sessionExercises.exercise.muscles')->findOrFail($mesocycleId);

        $currentWeek = $mesocycle->weeks->firstWhere('week_number', $fromWeekNumber);
        $nextWeek = $mesocycle->weeks->firstWhere('week_number', $fromWeekNumber + 1);

        if ($currentWeek === null || $nextWeek === null) {
            return new ProgressionResult([], [], 'held', 'Settimana successiva non trovata.');
        }

        // Calcola volume effettivo settimana corrente
        $currentVolume = $this->volumeCalc->calculate($mesocycle->athlete_id, $currentWeek->id);

        // Analizza i segnali di feedback della settimana corrente vs precedente
        [$action, $feedbackTriggers] = $this->analyzeFeedback($mesocycle->athlete_id, $currentWeek, $mesocycle);

        if ($nextWeek->is_deload) {
            return $this->applyDeload($mesocycle, $currentWeek, $nextWeek, $currentVolume);
        }

        return $this->applyProgression($mesocycle, $nextWeek, $currentVolume, $action, $feedbackTriggers);
    }

    /**
     * Confronta i feedback della settimana corrente con quelli della precedente.
     * Restituisce [action, triggerNames].
     *
     * @return array{string, array<string>}
     */
    private function analyzeFeedback(int $athleteId, MicrocycleWeek $currentWeek, Mesocycle $mesocycle): array
    {
        $prevWeek = $mesocycle->weeks->firstWhere('week_number', $currentWeek->week_number - 1);

        if ($prevWeek === null) {
            return ['progressed', []];
        }

        $metrics = ['pump', 'soreness_prev', 'perceived_effort', 'joint_pain', 'performance'];

        $currentFeedbacks = SessionFeedback::whereIn(
            'session_id',
            TrainingSession::where('microcycle_week_id', $currentWeek->id)->where('status', 'completed')->select('id')
        )->get();

        $prevFeedbacks = SessionFeedback::whereIn(
            'session_id',
            TrainingSession::where('microcycle_week_id', $prevWeek->id)->where('status', 'completed')->select('id')
        )->get();

        if ($currentFeedbacks->isEmpty() || $prevFeedbacks->isEmpty()) {
            return ['progressed', []];
        }

        $degraded = [];

        foreach ($metrics as $metric) {
            $currMedian = $this->median($currentFeedbacks->pluck($metric)->filter()->values()->all());
            $prevMedian = $this->median($prevFeedbacks->pluck($metric)->filter()->values()->all());

            if ($currMedian === null || $prevMedian === null) {
                continue;
            }

            // Per pump e performance: valori più alti = meglio (degrado se scende)
            // Per soreness_prev, perceived_effort, joint_pain: valori più alti = peggio (degrado se sale)
            $worse = in_array($metric, ['pump', 'performance'])
                ? $currMedian < $prevMedian
                : $currMedian > $prevMedian;

            if ($worse) {
                $degraded[] = $metric;
            }
        }

        $count = count($degraded);

        if ($count >= 3) {
            return ['reduced', $degraded];
        }
        if ($count === 2) {
            return ['held', $degraded];
        }

        return ['progressed', $degraded];
    }

    /**
     * Applica la progressione standard (+1 set per muscolo sotto MRV) o riduzione.
     *
     * @param  array<string, array{hard_sets: float, mev: int|null, mav_min: int|null, mav_max: int|null, mrv: int|null, status: string}>  $currentVolume
     * @param  array<string>  $feedbackTriggers
     */
    private function applyProgression(
        Mesocycle $mesocycle,
        MicrocycleWeek $nextWeek,
        array $currentVolume,
        string $action,
        array $feedbackTriggers
    ): ProgressionResult {
        $delta = match ($action) {
            'progressed' => +1,
            'held' => 0,
            'reduced' => -1,
            default => 0,
        };

        $setsAddedByMuscle = [];
        $note = null;

        if ($action === 'reduced') {
            $note = 'Feedback in peggioramento su 3+ metriche. Volume ridotto. Valutare deload anticipato.';
        }

        foreach ($currentVolume as $slug => $data) {
            if ($data['mrv'] === null) {
                continue;
            }

            $canAdd = $delta > 0 && $data['hard_sets'] < $data['mrv'];
            $canRemove = $delta < 0;
            $setsAddedByMuscle[$slug] = ($canAdd || $canRemove) ? $delta : 0;
        }

        // Distribuisce i set delta tra gli esercizi della settimana successiva
        $this->distributeSetsDelta($mesocycle, $nextWeek, $setsAddedByMuscle, $delta);

        return new ProgressionResult($setsAddedByMuscle, $feedbackTriggers, $action, $note);
    }

    /**
     * Applica la settimana deload: 50% dei planned_sets_count della settimana corrente.
     *
     * @param  array<string, array{hard_sets: float, mev: int|null, mav_min: int|null, mav_max: int|null, mrv: int|null, status: string}>  $currentVolume
     */
    private function applyDeload(
        Mesocycle $mesocycle,
        MicrocycleWeek $currentWeek,
        MicrocycleWeek $nextWeek,
        array $currentVolume
    ): ProgressionResult {
        // Per ogni esercizio della settimana deload, imposta planned_sets_count al 50% della corrente
        $currentSessionExercises = DB::table('session_exercises')
            ->join('training_sessions', 'training_sessions.id', '=', 'session_exercises.session_id')
            ->where('training_sessions.microcycle_week_id', $currentWeek->id)
            ->select('session_exercises.exercise_id', DB::raw('AVG(session_exercises.planned_sets_count) as avg_sets'), DB::raw('MAX(session_exercises.planned_sets_count) as max_sets'))
            ->groupBy('session_exercises.exercise_id')
            ->get()
            ->keyBy('exercise_id');

        $nextSessions = TrainingSession::where('microcycle_week_id', $nextWeek->id)
            ->with('sessionExercises')
            ->get();

        foreach ($nextSessions as $session) {
            foreach ($session->sessionExercises as $se) {
                $ref = $currentSessionExercises[$se->exercise_id] ?? null;
                $currentSets = $ref ? (int) $ref->max_sets : $se->planned_sets_count;
                $deloadSets = max(1, (int) floor($currentSets / 2));

                // Nota con indicazione del peso target (~90%)
                $lastWeight = DB::table('exercise_sets')
                    ->join('session_exercises as sej', 'sej.id', '=', 'exercise_sets.session_exercise_id')
                    ->join('training_sessions as ts', 'ts.id', '=', 'sej.session_id')
                    ->where('ts.microcycle_week_id', $currentWeek->id)
                    ->where('sej.exercise_id', $se->exercise_id)
                    ->whereNotNull('exercise_sets.actual_weight_kg')
                    ->orderByDesc('exercise_sets.completed_at')
                    ->value('exercise_sets.actual_weight_kg');

                $trainerNote = $se->trainer_note;
                if ($lastWeight !== null) {
                    $targetKg = round($lastWeight * 0.9, 1);
                    $trainerNote = "[DELOAD] Peso target: {$targetKg} kg (~90% dell'ultimo carico usato). ".($se->trainer_note ?? '');
                }

                $se->update([
                    'planned_sets_count' => $deloadSets,
                    'trainer_note' => $trainerNote,
                ]);
            }
        }

        $setsAdded = array_fill_keys(array_keys($currentVolume), -1);

        return new ProgressionResult(
            $setsAdded,
            [],
            'deload',
            'Settimana deload: volume ridotto al 50%, carico al 90%.'
        );
    }

    /**
     * Distribuisce la variazione di set (+1 o -1 per muscolo) tra gli esercizi
     * della settimana successiva, pesando per contribution_pct dei primary muscles.
     *
     * @param  array<string, int>  $setsAddedByMuscle
     */
    private function distributeSetsDelta(Mesocycle $mesocycle, MicrocycleWeek $nextWeek, array $setsAddedByMuscle, int $delta): void
    {
        if ($delta === 0) {
            return;
        }

        $nextSessions = TrainingSession::where('microcycle_week_id', $nextWeek->id)
            ->with(['sessionExercises' => fn ($q) => $q->orderBy('order_in_session'), 'sessionExercises.exercise.muscles'])
            ->get();

        // Mappa exercise_id => session_exercise (usa il primo per order_in_session)
        $seByExercise = [];
        foreach ($nextSessions as $session) {
            foreach ($session->sessionExercises as $se) {
                $seByExercise[$se->exercise_id] = $se;
            }
        }

        if (empty($seByExercise)) {
            return;
        }

        // Carica dati muscle-exercise via DB per evitare accesso a ->pivot non tipizzato
        $primaryMusclesByExercise = DB::table('exercise_muscle')
            ->join('muscles', 'muscles.id', '=', 'exercise_muscle.muscle_id')
            ->whereIn('exercise_muscle.exercise_id', array_keys($seByExercise))
            ->where('exercise_muscle.role', 'primary')
            ->select('exercise_muscle.exercise_id', 'muscles.slug', 'exercise_muscle.contribution_pct')
            ->get()
            ->groupBy('exercise_id');

        // Per ogni muscolo con variazione, trova l'esercizio primary con contribution_pct maggiore
        $appliedToSe = []; // session_exercise_id => delta cumulato

        foreach ($setsAddedByMuscle as $slug => $muscleDelta) {
            if ($muscleDelta === 0) {
                continue;
            }

            $bestSeId = null;
            $bestPct = 0;

            foreach ($seByExercise as $exerciseId => $se) {
                $rows = $primaryMusclesByExercise[$exerciseId] ?? collect();
                foreach ($rows as $row) {
                    if ($row->slug === $slug && $row->contribution_pct > $bestPct) {
                        $bestPct = $row->contribution_pct;
                        $bestSeId = $se->id;
                    }
                }
            }

            if ($bestSeId !== null) {
                $appliedToSe[$bestSeId] = ($appliedToSe[$bestSeId] ?? 0) + $muscleDelta;
            }
        }

        // Applica i delta aggregati
        $seById = collect($seByExercise)->keyBy('id');
        foreach ($appliedToSe as $seId => $totalDelta) {
            $se = $seById[$seId] ?? null;
            if ($se === null) {
                continue;
            }
            $newCount = max(1, $se->planned_sets_count + $totalDelta);
            $se->update(['planned_sets_count' => $newCount]);
        }
    }

    /** @param array<int|float> $values */
    private function median(array $values): ?float
    {
        if (empty($values)) {
            return null;
        }
        sort($values);
        $count = count($values);
        $mid = (int) floor($count / 2);

        return $count % 2 === 0
            ? ($values[$mid - 1] + $values[$mid]) / 2
            : (float) $values[$mid];
    }
}
