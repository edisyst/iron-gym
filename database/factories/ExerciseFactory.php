<?php

namespace Database\Factories;

use App\Models\Exercise;
use App\Models\MovementPattern;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exercise>
 */
class ExerciseFactory extends Factory
{
    public function definition(): array
    {
        // Determina casualmente se compound o isolation per rispettare il CHECK XOR
        $isCompound = fake()->boolean();

        return [
            'slug' => fake()->unique()->slug(3),
            'name_it' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'mechanic' => $isCompound ? 'compound' : 'isolation',
            'plane' => fake()->randomElement(['sagittal', 'frontal', 'transverse', 'multiplanar']),
            'laterality' => fake()->randomElement(['bilateral', 'unilateral_alternating', 'unilateral_isolated']),
            'skill_level' => fake()->randomElement(['beginner', 'intermediate', 'advanced']),
            'measurement_type' => 'reps_weight',
            // XOR: esattamente uno dei due è valorizzato
            'compound_pattern_id' => $isCompound
                ? MovementPattern::where('category', 'compound_pattern')->inRandomOrder()->first()?->id
                : null,
            'joint_action_id' => ! $isCompound
                ? MovementPattern::where('category', 'joint_action')->inRandomOrder()->first()?->id
                : null,
        ];
    }
}
