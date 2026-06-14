<?php

namespace Database\Factories;

use App\Models\SessionFeedback;
use App\Models\TrainingSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionFeedback>
 */
class SessionFeedbackFactory extends Factory
{
    public function definition(): array
    {
        return [
            'session_id' => TrainingSession::factory(),
            'pump' => fake()->numberBetween(0, 3),
            'soreness_prev' => fake()->numberBetween(0, 3),
            'perceived_effort' => fake()->numberBetween(0, 3),
            'joint_pain' => fake()->numberBetween(0, 3),
            'performance' => fake()->numberBetween(0, 3),
            'sleep_hours' => null,
            'stress_level' => null,
            'note' => null,
        ];
    }
}
