<?php

namespace Database\Factories;

use App\Models\MicrocycleWeek;
use App\Models\TrainingSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingSession>
 */
class TrainingSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'microcycle_week_id' => MicrocycleWeek::factory(),
            'name' => fake()->randomElement(['Push A', 'Pull A', 'Legs A', 'Push B', 'Pull B', 'Gambe pesante', 'Full body']),
            'order_in_week' => fake()->numberBetween(1, 5),
            'scheduled_date' => fake()->dateTimeBetween('now', '+2 months'),
            'started_at' => null,
            'completed_at' => null,
            'status' => 'planned',
            'athlete_notes' => null,
            'trainer_notes' => null,
        ];
    }

    /**
     * Stato: sessione completata con timestamp realistici
     */
    public function completed(): static
    {
        return $this->state(function () {
            $started = fake()->dateTimeBetween('-3 months', '-1 day');
            $completed = (clone $started)->modify('+'.fake()->numberBetween(45, 120).' minutes');

            return [
                'status' => 'completed',
                'started_at' => $started,
                'completed_at' => $completed,
            ];
        });
    }

    /**
     * Stato: sessione in corso
     */
    public function inProgress(): static
    {
        return $this->state([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }
}
