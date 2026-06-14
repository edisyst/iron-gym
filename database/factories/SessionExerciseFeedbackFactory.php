<?php

namespace Database\Factories;

use App\Models\SessionExercise;
use App\Models\SessionExerciseFeedback;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionExerciseFeedback>
 */
class SessionExerciseFeedbackFactory extends Factory
{
    public function definition(): array
    {
        return [
            'session_exercise_id' => SessionExercise::factory(),
            'joint_pain' => fake()->numberBetween(0, 3),
            'pump' => fake()->numberBetween(0, 3),
            'note' => null,
        ];
    }
}
