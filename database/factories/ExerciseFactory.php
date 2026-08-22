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

        // firstOrCreate garantisce che esista almeno un record anche con DB vuoto (RefreshDatabase)
        $compoundPattern = $isCompound
            ? MovementPattern::firstOrCreate(
                ['slug' => 'test_compound_pattern'],
                ['name_it' => 'Compound Pattern', 'category' => 'compound_pattern', 'display_order' => 99]
            )
            : null;

        $jointAction = ! $isCompound
            ? MovementPattern::firstOrCreate(
                ['slug' => 'test_joint_action'],
                ['name_it' => 'Joint Action', 'category' => 'joint_action', 'display_order' => 99]
            )
            : null;

        return [
            'slug' => fake()->unique()->slug(3),
            'name_it' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'mechanic' => $isCompound ? 'compound' : 'isolation',
            'plane' => fake()->randomElement(['sagittal', 'frontal', 'transverse', 'multiplanar']),
            'laterality' => fake()->randomElement(['bilateral', 'unilateral_alternating', 'unilateral_isolated']),
            'skill_level' => fake()->randomElement(['beginner', 'intermediate', 'advanced']),
            'measurement_type' => 'reps_weight',
            'compound_pattern_id' => $compoundPattern?->id,
            'joint_action_id' => $jointAction?->id,
        ];
    }
}
