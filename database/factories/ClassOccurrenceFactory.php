<?php

namespace Database\Factories;

use App\Models\ClassOccurrence;
use App\Models\GroupClass;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassOccurrence>
 */
class ClassOccurrenceFactory extends Factory
{
    protected $model = ClassOccurrence::class;

    public function definition(): array
    {
        $date = fake()->dateTimeBetween('+1 day', '+30 days');
        $start = Carbon::instance($date)->setTime(fake()->numberBetween(7, 20), 0, 0);
        $duration = fake()->randomElement([45, 60, 75, 90]);
        $end = $start->copy()->addMinutes($duration);

        return [
            'group_class_id'    => GroupClass::factory(),
            'class_schedule_id' => null,
            'date'              => $start->toDateString(),
            'start_time'        => $start->format('H:i:s'),
            'end_time'          => $end->format('H:i:s'),
            'trainer_id'        => User::factory(),
            'capacity'          => fake()->numberBetween(5, 20),
            'status'            => 'planned',
        ];
    }

    public function planned(): static
    {
        return $this->state(['status' => 'planned']);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status'              => 'cancelled',
            'cancellation_reason' => 'Corso cancellato.',
        ]);
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed']);
    }

    public function past(): static
    {
        return $this->state(function () {
            $date = fake()->dateTimeBetween('-30 days', '-1 day');
            $start = \Carbon\Carbon::instance($date)->setTime(9, 0, 0);
            $end = $start->copy()->addHour();

            return [
                'date'       => $start->toDateString(),
                'start_time' => $start->format('H:i:s'),
                'end_time'   => $end->format('H:i:s'),
                'status'     => 'completed',
            ];
        });
    }
}
