<?php

namespace App\Services;

use App\Models\PersonalRecord;
use App\Models\TrainingSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SessionRecapBuilder
{
    /**
     * Costruisce il riepilogo di una sessione completata.
     * Cinque query separate, nessun N+1.
     *
     * @return array{
     *   duration_minutes: int|null,
     *   tonnage_kg: float,
     *   sets_completed: int,
     *   sets_prescribed: int,
     *   prs: Collection<int, PersonalRecord>,
     *   top_muscles: list<array{name_it: string, slug: string, score: int}>
     * }
     */
    public function build(TrainingSession $session, int $athleteId): array
    {
        $duration = ($session->started_at && $session->completed_at)
            ? (int) $session->started_at->diffInMinutes($session->completed_at)
            : null;

        $tonnage = (float) DB::table('exercise_sets as es')
            ->join('session_exercises as se', 'se.id', '=', 'es.session_exercise_id')
            ->where('se.session_id', $session->id)
            ->where('es.is_warmup', false)
            ->whereNotNull('es.completed_at')
            ->whereNotNull('es.actual_reps')
            ->whereNotNull('es.actual_weight_kg')
            ->sum(DB::raw('es.actual_reps * es.actual_weight_kg'));

        $counts = DB::table('exercise_sets as es')
            ->join('session_exercises as se', 'se.id', '=', 'es.session_exercise_id')
            ->where('se.session_id', $session->id)
            ->where('es.is_warmup', false)
            ->selectRaw('COUNT(CASE WHEN es.completed_at IS NOT NULL THEN 1 END) as completed, COUNT(*) as prescribed')
            ->first();

        $prs = collect();
        if ($session->started_at && $session->completed_at) {
            $prs = PersonalRecord::where('athlete_id', $athleteId)
                ->whereBetween('achieved_at', [$session->started_at, $session->completed_at])
                ->with('exercise')
                ->get();
        }

        $topMuscles = DB::table('exercise_sets as es')
            ->join('session_exercises as se', 'se.id', '=', 'es.session_exercise_id')
            ->join('exercise_muscle as em', 'em.exercise_id', '=', 'se.exercise_id')
            ->join('muscles as m', 'm.id', '=', 'em.muscle_id')
            ->where('se.session_id', $session->id)
            ->where('es.is_warmup', false)
            ->whereNotNull('es.completed_at')
            ->groupBy('m.id', 'm.name_it', 'm.slug')
            ->orderByDesc('score')
            ->limit(3)
            ->selectRaw('m.id, m.name_it, m.slug, SUM(em.contribution_pct) as score')
            ->get()
            ->map(fn ($row) => [
                'name_it' => $row->name_it,
                'slug' => $row->slug,
                'score' => (int) $row->score,
            ])
            ->values()
            ->all();

        return [
            'duration_minutes' => $duration,
            'tonnage_kg' => $tonnage,
            'sets_completed' => (int) ($counts->completed ?? 0),
            'sets_prescribed' => (int) ($counts->prescribed ?? 0),
            'prs' => $prs,
            'top_muscles' => $topMuscles,
        ];
    }
}
