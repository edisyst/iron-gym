<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\PtBooking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PtBooking>
 */
class PtBookingFactory extends Factory
{
    protected $model = PtBooking::class;

    public function definition(): array
    {
        $date = fake()->dateTimeBetween('now', '+30 days');
        $start = fake()->randomElement(['09:00', '10:00', '11:00', '15:00', '16:00', '17:00']);

        return [
            'trainer_id' => User::factory(),
            'member_id' => Member::factory(),
            'session_id' => null,
            'booked_date' => $date->format('Y-m-d'),
            'start_time' => $start,
            'end_time' => null,
            'status' => 'confirmed',
            'cancelled_by' => null,
            'cancellation_reason' => null,
            'cancellation_deadline' => null,
            'notes' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }
}
