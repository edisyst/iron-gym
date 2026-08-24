<?php

namespace Database\Factories;

use App\Models\GroupClass;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GroupClass>
 */
class GroupClassFactory extends Factory
{
    protected $model = GroupClass::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Spinning', 'Yoga', 'CrossFit', 'Pilates', 'Zumba',
            'Functional Training', 'Calisthenics', 'HIIT', 'Body Pump', 'Stretching',
        ]);

        return [
            'slug'             => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 9999),
            'name'             => $name,
            'description'      => fake()->optional()->sentence(),
            'duration_minutes' => fake()->randomElement([45, 60, 75, 90]),
            'default_capacity' => fake()->numberBetween(5, 20),
            'room'             => null,
            'color'            => null,
            'is_active'        => true,
        ];
    }
}
