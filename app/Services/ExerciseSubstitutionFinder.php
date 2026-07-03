<?php

namespace App\Services;

use App\Models\Exercise;
use Illuminate\Support\Collection;

class ExerciseSubstitutionFinder
{
    /** @var array<string, int> */
    private const SKILL_RANK = ['beginner' => 0, 'intermediate' => 1, 'advanced' => 2];

    private const MAX_RESULTS = 5;

    /**
     * Restituisce i primi MAX_RESULTS candidati sostitutivi per $original.
     *
     * Ogni elemento contiene:
     *   - exercise:        Exercise (con muscles, primaryMuscles e equipment già caricati)
     *   - overlap:         int (somma dei min contribution_pct sui muscoli comuni)
     *   - same_mechanic:   0|1 (1 se stesso mechanic — usato per ranking)
     *   - skill_penalty:   int (0 se skill_level <= originale, > 0 altrimenti)
     *   - equipment_slugs: string[] (slug equipment richiesti)
     *   - primary_muscles: string[] (name_it dei muscoli primary)
     *
     * @return Collection<int, array{exercise: Exercise, overlap: int, same_mechanic: 0|1, skill_penalty: 0|1|2, equipment_slugs: list<string>, primary_muscles: list<string>}>
     */
    public function find(Exercise $original): Collection
    {
        $query = Exercise::query()->whereNull('deleted_at');

        if ($original->joint_action_id !== null) {
            $query->where('joint_action_id', $original->joint_action_id);
        } else {
            $query->where('compound_pattern_id', $original->compound_pattern_id);
        }

        $candidates = $query
            ->where('measurement_type', $original->measurement_type)
            ->where('id', '!=', $original->id)
            ->with([
                'muscles' => fn ($q) => $q->withPivot('role', 'contribution_pct'),
                'primaryMuscles',
                'equipment',
            ])
            ->get();

        if (! $original->relationLoaded('muscles')) {
            $original->load(['muscles' => fn ($q) => $q->withPivot('role', 'contribution_pct')]);
        }

        /** @var array<int, int> $origMap muscle_id → contribution_pct */
        $origMap = $original->muscles->pluck('pivot.contribution_pct', 'id')->all();

        $origSkillRank = self::SKILL_RANK[$original->skill_level];

        return $candidates
            ->map(function (Exercise $candidate) use ($origMap, $original, $origSkillRank): array {
                /** @var array<int, int> $candMap */
                $candMap = $candidate->muscles->pluck('pivot.contribution_pct', 'id')->all();

                $overlap = 0;
                foreach ($origMap as $muscleId => $origPct) {
                    if (isset($candMap[$muscleId])) {
                        $overlap += min((int) $origPct, (int) $candMap[$muscleId]);
                    }
                }

                $sameMechanic = $candidate->mechanic === $original->mechanic ? 1 : 0;
                $skillPenalty = max(0, self::SKILL_RANK[$candidate->skill_level] - $origSkillRank);

                /** @var list<string> $equipmentSlugs */
                $equipmentSlugs = $candidate->equipment->pluck('slug')->values()->all();

                /** @var list<string> $primaryMuscles */
                $primaryMuscles = $candidate->primaryMuscles->pluck('name_it')->values()->all();

                return [
                    'exercise' => $candidate,
                    'overlap' => $overlap,
                    'same_mechanic' => $sameMechanic,
                    'skill_penalty' => $skillPenalty,
                    'equipment_slugs' => $equipmentSlugs,
                    'primary_muscles' => $primaryMuscles,
                ];
            })
            ->sortBy([
                ['overlap', 'desc'],
                ['same_mechanic', 'desc'],
                ['skill_penalty', 'asc'],
            ])
            ->take(self::MAX_RESULTS)
            ->values();
    }
}
