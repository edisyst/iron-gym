<?php

namespace App\Services;

use App\Models\ExerciseSet;
use App\Models\PersonalRecord;
use Illuminate\Support\Facades\DB;

class PersonalRecordDetector
{
    /**
     * Verifica se il set completo costituisce un nuovo PR e1RM.
     * Restituisce il PersonalRecord creato, oppure null se non è un PR.
     *
     * Strutturato per singola responsabilità: può essere invocato sia da
     * Livewire (online) sia dal SyncBatchController (offline). Pronto per
     * essere spostato in un evento+listener senza modifiche all'interfaccia.
     */
    public function check(ExerciseSet $set, int $athleteId): ?PersonalRecord
    {
        if (! $this->isEligible($set)) {
            return null;
        }

        $exercise = $set->sessionExercise->exercise;
        $e1rm = E1rmCalculator::epley(
            (float) $set->actual_weight_kg,
            (int) $set->actual_reps
        );

        if ($e1rm === null) {
            return null;
        }

        if (! $this->hasSufficientHistory($athleteId, $exercise->id)) {
            return null;
        }

        $currentBest = PersonalRecord::where('athlete_id', $athleteId)
            ->where('exercise_id', $exercise->id)
            ->where('record_type', 'e1rm')
            ->max('value');

        if ($currentBest !== null && $e1rm <= (float) $currentBest) {
            return null;
        }

        return PersonalRecord::create([
            'athlete_id' => $athleteId,
            'exercise_id' => $exercise->id,
            'exercise_set_id' => $set->id,
            'record_type' => 'e1rm',
            'value' => $e1rm,
            'achieved_at' => now(),
        ]);
    }

    private function isEligible(ExerciseSet $set): bool
    {
        if ($set->is_warmup) {
            return false;
        }

        if ($set->actual_weight_kg === null || $set->actual_reps === null) {
            return false;
        }

        if ($set->sessionExercise->exercise->measurement_type !== 'reps_weight') {
            return false;
        }

        if ((int) $set->actual_reps > config('pr.max_reps_epley', 12)) {
            return false;
        }

        return true;
    }

    private function hasSufficientHistory(int $athleteId, int $exerciseId): bool
    {
        $minSessions = config('pr.min_sessions_before_pr', 3);

        $sessionCount = DB::table('exercise_sets as es')
            ->join('session_exercises as se', 'se.id', '=', 'es.session_exercise_id')
            ->join('training_sessions as ts', 'ts.id', '=', 'se.session_id')
            ->join('microcycle_weeks as mw', 'mw.id', '=', 'ts.microcycle_week_id')
            ->join('mesocycles as mc', 'mc.id', '=', 'mw.mesocycle_id')
            ->where('mc.athlete_id', $athleteId)
            ->where('se.exercise_id', $exerciseId)
            ->where('ts.status', 'completed')
            ->distinct('ts.id')
            ->count('ts.id');

        return $sessionCount >= $minSessions;
    }
}
