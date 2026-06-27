<?php

namespace Database\Factories;

use App\Models\BodyMeasurement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BodyMeasurement>
 */
class BodyMeasurementFactory extends Factory
{
    protected $model = BodyMeasurement::class;

    public function definition(): array
    {
        return [
            'athlete_id' => User::factory(),
            'measured_at' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'weight_kg' => fake()->randomFloat(1, 55, 120),
            'body_fat_pct' => fake()->optional(0.6)->randomFloat(1, 8, 30),
            'chest_cm' => fake()->optional(0.7)->randomFloat(1, 85, 120),
            'waist_cm' => fake()->optional(0.7)->randomFloat(1, 65, 100),
            'hips_cm' => fake()->optional(0.7)->randomFloat(1, 85, 110),
            'left_arm_cm' => fake()->optional(0.5)->randomFloat(1, 28, 50),
            'right_arm_cm' => fake()->optional(0.5)->randomFloat(1, 28, 50),
            'left_thigh_cm' => fake()->optional(0.5)->randomFloat(1, 48, 75),
            'right_thigh_cm' => fake()->optional(0.5)->randomFloat(1, 48, 75),
            'left_calf_cm' => fake()->optional(0.5)->randomFloat(1, 32, 48),
            'right_calf_cm' => fake()->optional(0.5)->randomFloat(1, 32, 48),
            'notes' => null,
            'recorded_by' => null,
        ];
    }
}
