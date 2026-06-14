<?php

namespace Database\Factories;

use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MicrocycleWeek>
 */
class MicrocycleWeekFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+3 months');
        $end = (clone $start)->modify('+6 days');

        return [
            'mesocycle_id' => Mesocycle::factory(),
            'week_number' => 1,
            'is_deload' => false,
            'start_date' => $start,
            'end_date' => $end,
        ];
    }
}
