<?php

namespace Database\Factories;

use App\Models\ClassSchedule;
use App\Models\GroupClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSchedule>
 */
class ClassScheduleFactory extends Factory
{
    protected $model = ClassSchedule::class;

    public function definition(): array
    {
        return [
            'group_class_id' => GroupClass::factory(),
            'weekday' => fake()->numberBetween(0, 6),
            'start_time' => fake()->randomElement(['07:00:00', '09:00:00', '10:30:00', '18:00:00', '19:30:00']),
            'trainer_id' => User::factory(),
            'valid_from' => now()->toDateString(),
            'valid_until' => null,
            'is_active' => true,
        ];
    }
}
