<?php

namespace App\Services;

use App\Models\MicrocycleWeek;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WeeklyVolumeCalculator
{
    /**
     * Calcola il volume settimanale per muscolo per una settimana del mesociclo.
     * Usa una singola query JOIN per minimizzare i round-trip al DB.
     * Risultato cachato per 15 minuti per chiave volume:{athleteId}:{weekId}.
     *
     * @return array<string, array{hard_sets: float, mev: int|null, mav_min: int|null, mav_max: int|null, mrv: int|null, status: string}>
     */
    public function calculate(int $athleteId, int $microcycleWeekId): array
    {
        $cacheKey = "volume:{$athleteId}:{$microcycleWeekId}";

        return Cache::remember($cacheKey, 900, function () use ($athleteId, $microcycleWeekId) {
            return $this->computeVolume($athleteId, $microcycleWeekId);
        });
    }

    /**
     * Invalida la cache del volume per una settimana specifica.
     */
    public function forget(int $athleteId, int $microcycleWeekId): void
    {
        Cache::forget("volume:{$athleteId}:{$microcycleWeekId}");
    }

    /**
     * @return array<string, array{hard_sets: float, mev: int|null, mav_min: int|null, mav_max: int|null, mrv: int|null, status: string}>
     */
    private function computeVolume(int $athleteId, int $microcycleWeekId): array
    {
        // Verifica esistenza settimana (query 1)
        MicrocycleWeek::findOrFail($microcycleWeekId);

        // Singola query JOIN: aggrega hard_sets per muscolo (query 2)
        $muscleRows = DB::table('exercise_sets as es')
            ->join('session_exercises as se', 'se.id', '=', 'es.session_exercise_id')
            ->join('training_sessions as ts', 'ts.id', '=', 'se.session_id')
            ->join('exercise_muscle as em', 'em.exercise_id', '=', 'se.exercise_id')
            ->join('muscles', 'muscles.id', '=', 'em.muscle_id')
            ->where('ts.microcycle_week_id', $microcycleWeekId)
            ->where('ts.status', 'completed')
            ->where('es.is_warmup', false)
            ->whereNotNull('es.completed_at')
            ->select('muscles.slug', DB::raw('SUM(em.contribution_pct / 100.0) as hard_sets_total'))
            ->groupBy('muscles.id', 'muscles.slug')
            ->get()
            ->keyBy('slug');

        $volume = [];
        foreach ($muscleRows as $slug => $row) {
            $volume[$slug] = round((float) $row->hard_sets_total, 2);
        }

        // Landmark atleta con override DB (query 3)
        $dbLandmarks = DB::table('athlete_volume_landmarks')
            ->join('muscles', 'muscles.id', '=', 'athlete_volume_landmarks.muscle_id')
            ->where('athlete_volume_landmarks.athlete_id', $athleteId)
            ->select('muscles.slug', 'athlete_volume_landmarks.mev', 'athlete_volume_landmarks.mav_min', 'athlete_volume_landmarks.mav_max', 'athlete_volume_landmarks.mrv')
            ->get()
            ->keyBy('slug')
            ->toArray();

        $defaultLandmarks = config('volume_landmarks', []);

        $result = [];
        $allSlugs = array_unique(array_merge(array_keys($volume), array_keys($defaultLandmarks)));

        foreach ($allSlugs as $slug) {
            $hardSets = $volume[$slug] ?? 0.0;

            if (isset($dbLandmarks[$slug])) {
                $lm = (array) $dbLandmarks[$slug];
            } elseif (isset($defaultLandmarks[$slug])) {
                $lm = $defaultLandmarks[$slug];
            } else {
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
