<?php

namespace Database\Factories;

use App\Models\Mesocycle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mesocycle>
 */
class MesocycleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'athlete_id' => User::factory(),
            'trainer_id' => User::factory(),
            'template_id' => null,
            'name' => fake()->words(3, true),
            'goal' => fake()->randomElement(['hypertrophy', 'strength', 'cut', 'recomp', 'peaking', 'general']),
            'periodization_model' => fake()->randomElement(['linear', 'undulating_dup', 'block']),
            'start_date' => fake()->dateTimeBetween('-2 months', 'now'),
            'weeks_count' => fake()->numberBetween(4, 6),
            'status' => 'draft',
            'notes' => null,
        ];
    }

    /**
     * Stato: mesociclo attivo
     */
    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    /**
     * Stato: mesociclo completato
     */
    public function completed(): static
    {
        return $this->state(['status' => 'completed']);
    }
}
