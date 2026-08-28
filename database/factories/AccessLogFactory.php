<?php

namespace Database\Factories;

use App\Models\AccessLog;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccessLog>
 */
class AccessLogFactory extends Factory
{
    protected $model = AccessLog::class;

    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'subscription_id' => null,
            'checked_in_at' => fake()->dateTimeBetween('-3 months', 'now'),
            'checked_in_by' => null,
            'note' => null,
        ];
    }

    public function peak(): static
    {
        return $this->state(function () {
            $hour = fake()->randomElement([7, 8, 9, 12, 13, 18, 19, 20, 21]);
            $minute = fake()->numberBetween(0, 59);

            return [
                'checked_in_at' => fake()->dateTimeBetween('-3 months', '-1 day')
                    ->setTime($hour, $minute),
            ];
        });
    }
}
