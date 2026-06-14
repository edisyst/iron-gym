<?php

namespace Database\Factories;

use App\Models\ExerciseSet;
use App\Models\SessionExercise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExerciseSet>
 */
class ExerciseSetFactory extends Factory
{
    public function definition(): array
    {
        $actualReps = fake()->numberBetween(8, 12);
        $actualWeight = fake()->randomFloat(1, 20, 100);
        $actualRir = fake()->numberBetween(0, 3);

        return [
            'session_exercise_id' => SessionExercise::factory(),
            'set_index' => 1,
            'set_sequence_id' => null,
            'sequence_index' => null,
            'set_subtype' => null,
            'is_warmup' => false,

            // Prescrizione
            'planned_reps' => fake()->numberBetween(8, 12),
            'planned_weight_kg' => null,
            'planned_rir' => fake()->numberBetween(1, 3),
            'planned_rpe' => null,
            'planned_duration_sec' => null,

            // Esecuzione (valorizzata per simulare set completati)
            'actual_reps' => $actualReps,
            'actual_weight_kg' => $actualWeight,
            'actual_rir' => $actualRir,
            'actual_rpe' => null,
            'actual_duration_sec' => null,
            'completed_at' => now(),
            'note' => null,
        ];
    }

    /**
     * Set non ancora completato (solo dati planned)
     */
    public function planned(): static
    {
        return $this->state([
            'actual_reps' => null,
            'actual_weight_kg' => null,
            'actual_rir' => null,
            'completed_at' => null,
        ]);
    }
}
