<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        $started = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'member_id' => Member::factory(),
            'plan_id' => SubscriptionPlan::factory(),
            'started_at' => $started->format('Y-m-d'),
            'expires_at' => (clone $started)->modify('+30 days')->format('Y-m-d'),
            'accesses_used' => 0,
            'accesses_remaining' => null,
            'status' => 'active',
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }

    public function expired(): static
    {
        return $this->state([
            'status' => 'expired',
            'started_at' => fake()->dateTimeBetween('-3 months', '-2 months')->format('Y-m-d'),
            'expires_at' => fake()->dateTimeBetween('-60 days', '-1 day')->format('Y-m-d'),
        ]);
    }
}
