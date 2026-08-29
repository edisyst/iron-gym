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
    protected $model = SessionExerciseFeedback::class;

    public function definition(): array
    {
        return [
            'session_exercise_id' => SessionExercise::factory(),
            'joint_pain' => $this->faker->numberBetween(0, 3),
            'pump' => $this->faker->numberBetween(0, 3),
            'note' => null,
        ];
    }
}
