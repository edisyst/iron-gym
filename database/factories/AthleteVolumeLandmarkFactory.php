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
        $mev = fake()->numberBetween(6, 10);
        $mavMin = $mev + fake()->numberBetween(2, 4);
        $mavMax = $mavMin + fake()->numberBetween(4, 8);
        $mrv = $mavMax + fake()->numberBetween(2, 4);

        return [
            'athlete_id' => User::factory(),
            'muscle_id' => Muscle::factory(),
            'mev' => $mev,
            'mav_min' => $mavMin,
            'mav_max' => $mavMax,
            'mrv' => $mrv,
            'notes' => null,
            'updated_by' => null,
        ];
    }
}
