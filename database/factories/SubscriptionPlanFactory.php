<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'price_cents' => fake()->randomElement([3000, 5000, 8000, 10000, 15000]),
            'duration_days' => fake()->randomElement([30, 60, 90, 180, 365]),
            'max_accesses' => fake()->optional(0.4)->numberBetween(8, 30),
            'is_active' => true,
        ];
    }
}
