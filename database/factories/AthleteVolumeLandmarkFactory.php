<?php

namespace Database\Factories;

use App\Models\AthleteVolumeLandmark;
use App\Models\Muscle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AthleteVolumeLandmark>
 */
class AthleteVolumeLandmarkFactory extends Factory
{
    protected $model = AthleteVolumeLandmark::class;

    public function definition(): array
    {
        return [
            'athlete_id' => User::factory(),
            'muscle_id' => Muscle::factory(),
            'mev' => $this->faker->numberBetween(6, 10),
            'mav_min' => $this->faker->numberBetween(12, 16),
            'mav_max' => $this->faker->numberBetween(16, 20),
            'mrv' => $this->faker->numberBetween(20, 26),
            'notes' => null,
            'updated_by' => null,
        ];
    }
}
