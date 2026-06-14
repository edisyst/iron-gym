<?php

namespace App\Services;

use App\Models\MicrocycleWeek;
use App\Models\TrainingSession;
use Illuminate\Support\Facades\DB;

class WeeklyVolumeCalculator
{
    /**
     * Calcola il volume settimanale per muscolo per una settimana del mesociclo.
     *
     * @return array<string, array{hard_sets: float, mev: int|null, mav_min: int|null, mav_max: int|null, mrv: int|null, status: string}>
     */
    public function calculate(int $athleteId, int $microcycleWeekId): array
    {
        $week = MicrocycleWeek::findOrFail($microcycleWeekId);

        // Sessioni completed della settimana con set working completati
        $sessions = TrainingSession::where('microcycle_week_id', $week->id)
            ->where('status', 'completed')
            ->with([
                'sessionExercises.sets' => fn ($q) => $q
                    ->where('is_warmup', false)
                    ->whereNotNull('completed_at'),
            ])
            ->get();

        // Raccoglie exercise_id distinti presenti nella settimana
        $exerciseIds = $sessions
            ->flatMap(fn ($s) => $s->sessionExercises->pluck('exercise_id'))
            ->unique()
            ->values()
            ->all();

        // Carica contribution_pct via DB per evitare accesso a ->pivot non tipizzato
        $musclePctByExercise = DB::table('exercise_muscle')
            ->join('muscles', 'muscles.id', '=', 'exercise_muscle.muscle_id')
            ->whereIn('exercise_muscle.exercise_id', $exerciseIds)
            ->select('exercise_muscle.exercise_id', 'muscles.slug', 'exercise_muscle.contribution_pct')
            ->get()
            ->groupBy('exercise_id');

        // Accumula hard_sets per muscle_slug
        /** @var array<string, float> $volume */
        $volume = [];

        foreach ($sessions as $session) {
            foreach ($session->sessionExercises as $se) {
                $workingSets = $se->sets;
                if ($workingSets->isEmpty()) {
                    continue;
                }

                $muscleRows = $musclePctByExercise[$se->exercise_id] ?? collect();

                foreach ($muscleRows as $row) {
                    $pct = $row->contribution_pct / 100;
                    $slug = $row->slug;

                    $volume[$slug] = ($volume[$slug] ?? 0.0) + $workingSets->count() * $pct;
                }
            }
        }

        // Carica i landmark dell'atleta dal DB (override del config)
        $dbLandmarks = DB::table('athlete_volume_landmarks')
            ->join('muscles', 'muscles.id', '=', 'athlete_volume_landmarks.muscle_id')
            ->where('athlete_volume_landmarks.athlete_id', $athleteId)
            ->select('muscles.slug', 'athlete_volume_landmarks.mev', 'athlete_volume_landmarks.mav_min', 'athlete_volume_landmarks.mav_max', 'athlete_volume_landmarks.mrv')
            ->get()
            ->keyBy('slug')
            ->toArray();

        $defaultLandmarks = config('volume_landmarks', []);

        $result = [];

        // Unisce muscoli con volume calcolato e muscoli con solo landmark
        $allSlugs = array_unique(array_merge(array_keys($volume), array_keys($defaultLandmarks)));

        foreach ($allSlugs as $slug) {
            $hardSets = round($volume[$slug] ?? 0.0, 2);

            if (isset($dbLandmarks[$slug])) {
                $lm = (array) $dbLandmarks[$slug];
            } elseif (isset($defaultLandmarks[$slug])) {
                $lm = $defaultLandmarks[$slug];
            } else {
                // Muscolo senza landmark (secondary/stabilizer): includi solo se ha volume
                if ($hardSets === 0.0) {
                    continue;
                }
                $result[$slug] = [
                    'hard_sets' => $hardSets,
                    'mev' => null,
                    'mav_min' => null,
                    'mav_max' => null,
                    'mrv' => null,
                    'status' => 'no_landmark',
                ];

                continue;
            }

            $result[$slug] = [
                'hard_sets' => $hardSets,
                'mev' => (int) $lm['mev'],
                'mav_min' => (int) $lm['mav_min'],
                'mav_max' => (int) $lm['mav_max'],
                'mrv' => (int) $lm['mrv'],
                'status' => $this->resolveStatus($hardSets, (int) $lm['mev'], (int) $lm['mav_min'], (int) $lm['mav_max'], (int) $lm['mrv']),
            ];
        }

        return $result;
    }

    private function resolveStatus(float $hardSets, int $mev, int $mavMin, int $mavMax, int $mrv): string
    {
        if ($hardSets > $mrv) {
            return 'over_mrv';
        }
        if ($hardSets >= $mavMax * 0.85) {
            return 'approaching_mrv';
        }
        if ($hardSets >= $mavMin) {
            return 'in_mav';
        }

        return 'below_mev';
    }
}
