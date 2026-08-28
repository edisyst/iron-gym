<?php

namespace Database\Factories;

use App\Models\Exercise;
use App\Models\ExerciseSet;
use App\Models\PersonalRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonalRecord>
 */
class PersonalRecordFactory extends Factory
{
    protected $model = PersonalRecord::class;

    public function definition(): array
    {
        $achieved = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'athlete_id' => User::factory(),
            'exercise_id' => Exercise::factory(),
            'exercise_set_id' => ExerciseSet::factory(),
            'record_type' => 'e1rm',
            'value' => fake()->randomFloat(2, 50.0, 200.0),
            'achieved_at' => $achieved,
            'created_at' => $achieved,
            'updated_at' => $achieved,
        ];
    }
}
