<?php

namespace Database\Factories;

use App\Models\Muscle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Muscle>
 */
class MuscleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'name_it' => fake()->words(2, true),
            'muscle_group' => fake()->randomElement(['chest', 'back', 'shoulders', 'arms', 'legs', 'core']),
            'muscle_head' => null,
            'display_order' => 0,
        ];
    }
}
