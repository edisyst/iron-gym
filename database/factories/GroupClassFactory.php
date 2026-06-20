<?php

namespace Database\Factories;

use App\Models\GroupClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GroupClass>
 */
class GroupClassFactory extends Factory
{
    protected $model = GroupClass::class;

    public function definition(): array
    {
        return [
            'trainer_id' => User::factory(),
            'name' => fake()->randomElement(['Spinning', 'Yoga', 'CrossFit', 'Pilates', 'Zumba']),
            'description' => fake()->optional()->sentence(),
            // Data futura di default
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+30 days'),
            'duration_minutes' => fake()->randomElement([45, 60, 75, 90]),
            'max_participants' => fake()->numberBetween(5, 20),
            'status' => 'scheduled',
        ];
    }
}
