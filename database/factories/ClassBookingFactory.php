<?php

namespace Database\Factories;

use App\Models\ClassBooking;
use App\Models\GroupClass;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassBooking>
 */
class ClassBookingFactory extends Factory
{
    protected $model = ClassBooking::class;

    public function definition(): array
    {
        return [
            'class_id' => GroupClass::factory(),
            'member_id' => Member::factory(),
            'status' => 'confirmed',
            'position' => null,
        ];
    }

    public function waitlisted(): static
    {
        return $this->state([
            'status' => 'waitlisted',
            'position' => fake()->numberBetween(1, 10),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled', 'position' => null]);
    }
}
