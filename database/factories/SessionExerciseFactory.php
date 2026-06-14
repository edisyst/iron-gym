<?php

namespace Database\Factories;

use App\Models\Exercise;
use App\Models\SessionExercise;
use App\Models\TrainingSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionExercise>
 */
class SessionExerciseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'session_id' => TrainingSession::factory(),
            'group_id' => null,
            'exercise_id' => Exercise::factory(),
            'order_in_session' => fake()->numberBetween(1, 10),
            'order_in_group' => null,
            'technique_type' => 'straight',
            'tempo' => null,
            'planned_sets_count' => fake()->numberBetween(2, 4),
            'planned_rest_sec' => fake()->randomElement([90, 120, 180]),
            'intra_cluster_rest_sec' => null,
            'trainer_note' => null,
        ];
    }
}
