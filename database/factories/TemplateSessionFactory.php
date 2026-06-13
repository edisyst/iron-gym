<?php

namespace Database\Factories;

use App\Models\TemplateSession;
use App\Models\WorkoutTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemplateSession>
 */
class TemplateSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'template_id' => WorkoutTemplate::factory(),
            'week_number' => 1,
            'name' => 'Sessione A',
            'order_in_week' => 1,
        ];
    }
}
