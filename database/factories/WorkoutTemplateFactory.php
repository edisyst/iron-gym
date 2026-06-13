<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkoutTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkoutTemplate>
 */
class WorkoutTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'goal' => fake()->randomElement(['hypertrophy', 'strength', 'cut', 'recomp', 'peaking', 'general']),
            'periodization_model' => fake()->randomElement(['linear', 'undulating_dup', 'block']),
            'weeks_count' => fake()->numberBetween(4, 6),
            'days_per_week' => fake()->numberBetween(2, 6),
            'created_by' => User::factory(),
            'is_active' => true,
        ];
    }
}
