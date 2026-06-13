<?php

namespace Database\Factories;

use App\Models\Exercise;
use App\Models\TemplateSession;
use App\Models\TemplateSessionExercise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemplateSessionExercise>
 */
class TemplateSessionExerciseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'template_session_id' => TemplateSession::factory(),
            'exercise_id' => Exercise::factory(),
            'order_in_session' => 1,
            'technique_type' => 'straight',
            'planned_sets_count' => 3,
            'planned_reps' => 10,
            'planned_rir' => 2,
            'planned_rest_sec' => 90,
        ];
    }
}
