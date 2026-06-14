<?php

namespace App\Services;

use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\TrainingSession;
use App\ValueObjects\DeloadSignal;
use Illuminate\Support\Facades\DB;

class DeloadEvaluator
{
    // Muscoli principali monitorati per il trigger MRV
    private const PRIMARY_MUSCLES = [
        'quadriceps',
        'pectoralis_major_sternal',
        'latissimus_dorsi',
        'hamstrings',
        'gluteus_maximus',
    ];

    public function __construct(private WeeklyVolumeCalculator $volumeCalc) {}

    /**
     * Valuta se il mesociclo ha trigger di deload attivi.
     */
    public function evaluate(int $mesocycleId): DeloadSignal
    {
        $mesocycle = Mesocycle::with('weeks')->findOrFail($mesocycleId);

        $activeTriggers = [];
        $notes = [];

        $currentWeek = $this->currentWeek($mesocycle);

        if ($currentWeek === null) {
            return new DeloadSignal([], null, 'Nessuna settimana corrente trovata.');
        }

        // Trigger 1: MRV raggiunto per ≥ 2 muscoli principali
        $mrvTrigger = $this->checkMrvTrigger($mesocycle->athlete_id, $currentWeek->id);
        if ($mrvTrigger !== null) {
            $activeTriggers[] = 'mrv_reached';
            $notes[] = $mrvTrigger;
        }

        // Trigger 2: joint_pain ≥ 2 per due settimane consecutive sullo stesso esercizio
        $jointPainTrigger = $this->checkJointPainTrigger($mesocycle, $currentWeek);
        if ($jointPainTrigger !== null) {
            $activeTriggers[] = 'persistent_joint_pain';
            $notes[] = $jointPainTrigger;
        }

        // Trigger 3: RIR drift (actual RIR < planned RIR di ≥ 2 negli ultimi 3 allenamenti dello stesso esercizio)
        $rirDrift = $this->checkRirDrift($mesocycle, $currentWeek);
        if ($rirDrift !== null) {
            $activeTriggers[] = 'rir_drift';
            $notes[] = $rirDrift;
        }

        // Trigger 4: fine programmata del mesociclo (ultima settimana, non deload)
        if ($this->checkEndOfMesocycle($mesocycle, $currentWeek)) {
            $activeTriggers[] = 'end_of_mesocycle';
            $notes[] = 'Fine programmata del mesociclo raggiunta.';
        }

        $suggestedWeek = empty($activeTriggers) ? null : $currentWeek->week_number + 1;

        return new DeloadSignal(
            $activeTriggers,
            $suggestedWeek,
            empty($notes) ? null : implode(' | ', $notes)
        );
    }

    /**
     * Trova la settimana corrente (la più avanzata con almeno una sessione completed).
     */
    private function currentWeek(Mesocycle $mesocycle): ?MicrocycleWeek
    {
        foreach ($mesocycle->weeks->sortByDesc('week_number') as $week) {
            $hasCompleted = TrainingSession::where('microcycle_week_id', $week->id)
                ->where('status', 'completed')
                ->exists();

            if ($hasCompleted) {
                return $week;
            }
        }

        return $mesocycle->weeks->sortBy('week_number')->first();
    }

    private function checkMrvTrigger(int $athleteId, int $weekId): ?string
    {
        $volume = $this->volumeCalc->calculate($athleteId, $weekId);
        $overMrv = [];

        foreach (self::PRIMARY_MUSCLES as $slug) {
            if (isset($volume[$slug]) && $volume[$slug]['status'] === 'over_mrv') {
                $overMrv[] = $slug;
            }
        }

        if (count($overMrv) >= 2) {
            return 'MRV superato per: '.implode(', ', $overMrv).'.';
        }

        return null;
    }

    private function checkJointPainTrigger(Mesocycle $mesocycle, MicrocycleWeek $currentWeek): ?string
    {
        $prevWeek = $mesocycle->weeks->firstWhere('week_number', $currentWeek->week_number - 1);

        if ($prevWeek === null) {
            return null;
        }

        // Raccoglie exercise_id => max joint_pain per settimana
        $painByExercise = fn (MicrocycleWeek $week) => DB::table('session_exercise_feedbacks')
            ->join('session_exercises', 'session_exercises.id', '=', 'session_exercise_feedbacks.session_exercise_id')
            ->join('training_sessions', 'training_sessions.id', '=', 'session_exercises.session_id')
            ->where('training_sessions.microcycle_week_id', $week->id)
            ->whereNotNull('session_exercise_feedbacks.joint_pain')
            ->select('session_exercises.exercise_id', DB::raw('MAX(session_exercise_feedbacks.joint_pain) as max_pain'))
            ->groupBy('session_exercises.exercise_id')
            ->get()
            ->keyBy('exercise_id');

        $currentPain = $painByExercise($currentWeek);
        $prevPain = $painByExercise($prevWeek);

        $persistent = [];

        foreach ($currentPain as $exerciseId => $row) {
            if ($row->max_pain >= 2 && isset($prevPain[$exerciseId]) && $prevPain[$exerciseId]->max_pain >= 2) {
                $persistent[] = $exerciseId;
            }
        }

        if (! empty($persistent)) {
            return 'Dolore articolare persistente (≥2) per 2 settimane consecutive su '.count($persistent).' esercizio/i.';
        }

        return null;
    }

    private function checkRirDrift(Mesocycle $mesocycle, MicrocycleWeek $currentWeek): ?string
    {
        // Ultimi 3 set working per esercizio, confronta actual_rir vs planned_rir
        $driftExercises = DB::table('exercise_sets')
            ->join('session_exercises', 'session_exercises.id', '=', 'exercise_sets.session_exercise_id')
            ->join('training_sessions', 'training_sessions.id', '=', 'session_exercises.session_id')
            ->join('microcycle_weeks', 'microcycle_weeks.id', '=', 'training_sessions.microcycle_week_id')
            ->where('microcycle_weeks.mesocycle_id', $mesocycle->id)
            ->where('microcycle_weeks.week_number', '<=', $currentWeek->week_number)
            ->where('exercise_sets.is_warmup', false)
            ->whereNotNull('exercise_sets.actual_rir')
            ->whereNotNull('exercise_sets.planned_rir')
            ->select(
                'session_exercises.exercise_id',
                'exercise_sets.actual_rir',
                'exercise_sets.planned_rir',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY session_exercises.exercise_id ORDER BY exercise_sets.completed_at DESC) as rn')
            )
            ->get()
            ->filter(fn ($row) => $row->rn <= 3)
            ->groupBy('exercise_id');

        $drifting = [];

        foreach ($driftExercises as $exerciseId => $sets) {
            // Tutti e 3 gli ultimi set hanno actual_rir < planned_rir - 2
            $allDrift = $sets->every(fn ($s) => ($s->planned_rir - $s->actual_rir) >= 2);
            if ($allDrift && $sets->count() >= 3) {
                $drifting[] = $exerciseId;
            }
        }

        if (! empty($drifting)) {
            return 'RIR drift su '.count($drifting).' esercizio/i: atleta non riesce a mantenere il RIR target prescritto.';
        }

        return null;
    }

    private function checkEndOfMesocycle(Mesocycle $mesocycle, MicrocycleWeek $currentWeek): bool
    {
        $lastWeek = $mesocycle->weeks->sortByDesc('week_number')->first();

        return $lastWeek !== null
            && $currentWeek->week_number === $lastWeek->week_number
            && ! $currentWeek->is_deload;
    }
}
