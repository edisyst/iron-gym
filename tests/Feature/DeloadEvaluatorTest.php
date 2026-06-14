<?php

use App\Models\Exercise;
use App\Models\ExerciseSet;
use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\Muscle;
use App\Models\SessionExercise;
use App\Models\SessionExerciseFeedback;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\DeloadEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $athlete = User::factory()->create();
    $trainer = User::factory()->create();

    $this->mesocycle = Mesocycle::factory()->create([
        'athlete_id' => $athlete->id,
        'trainer_id' => $trainer->id,
        'weeks_count' => 5,
    ]);

    $this->week1 = MicrocycleWeek::factory()->create(['mesocycle_id' => $this->mesocycle->id, 'week_number' => 1, 'is_deload' => false]);
    $this->week2 = MicrocycleWeek::factory()->create(['mesocycle_id' => $this->mesocycle->id, 'week_number' => 2, 'is_deload' => false]);
    $this->week5 = MicrocycleWeek::factory()->create(['mesocycle_id' => $this->mesocycle->id, 'week_number' => 5, 'is_deload' => false]);

    $this->athleteId = $athlete->id;
    $this->evaluator = app(DeloadEvaluator::class);
});

/** Attacca contribution_pct per il muscolo */
function attachMuscleWithPct(Exercise $exercise, Muscle $muscle, int $pct = 100): void
{
    $exercise->muscles()->attach($muscle->id, ['role' => 'primary', 'contribution_pct' => $pct]);
}

it('il deload è suggerito se MRV è raggiunto per due muscoli principali', function () {
    // Crea 2 muscoli principali con MRV basso e molti set
    $quad = Muscle::factory()->create(['slug' => 'quadriceps',            'name_it' => 'Quad',    'muscle_group' => 'legs']);
    $pec = Muscle::factory()->create(['slug' => 'pectoralis_major_sternal', 'name_it' => 'Petto', 'muscle_group' => 'chest']);

    $squat = Exercise::factory()->create(['slug' => 'back_squat_high_bar', 'name_it' => 'Squat', 'mechanic' => 'compound']);
    $bench = Exercise::factory()->create(['slug' => 'barbell_bench_press',  'name_it' => 'Panca', 'mechanic' => 'compound']);
    attachMuscleWithPct($squat, $quad, 100);
    attachMuscleWithPct($bench, $pec, 100);

    // Crea sessione completed con 30 set squat (molto sopra MRV=24) e 30 set panca (sopra MRV=22)
    $session = TrainingSession::factory()->create(['microcycle_week_id' => $this->week1->id, 'status' => 'completed']);

    foreach ([$squat, $bench] as $ex) {
        $se = SessionExercise::factory()->create(['session_id' => $session->id, 'exercise_id' => $ex->id, 'planned_sets_count' => 30]);
        ExerciseSet::factory()->count(30)->create([
            'session_exercise_id' => $se->id,
            'is_warmup' => false,
            'completed_at' => now(),
        ]);
    }

    $signal = $this->evaluator->evaluate($this->mesocycle->id);

    expect($signal->isDeloadNeeded())->toBeTrue();
    expect($signal->activeTriggers)->toContain('mrv_reached');
});

it('il deload è suggerito con joint pain persistente su due settimane', function () {
    $quad = Muscle::factory()->create(['slug' => 'quadriceps', 'name_it' => 'Quad', 'muscle_group' => 'legs']);
    $squat = Exercise::factory()->create(['slug' => 'back_squat_high_bar', 'name_it' => 'Squat', 'mechanic' => 'compound']);
    attachMuscleWithPct($squat, $quad, 100);

    foreach ([$this->week1, $this->week2] as $week) {
        $session = TrainingSession::factory()->create(['microcycle_week_id' => $week->id, 'status' => 'completed']);
        $se = SessionExercise::factory()->create(['session_id' => $session->id, 'exercise_id' => $squat->id, 'planned_sets_count' => 3]);
        ExerciseSet::factory()->count(3)->create(['session_exercise_id' => $se->id, 'is_warmup' => false, 'completed_at' => now()]);
        SessionExerciseFeedback::create([
            'session_exercise_id' => $se->id,
            'joint_pain' => 3,
            'pump' => null,
        ]);
    }

    $signal = $this->evaluator->evaluate($this->mesocycle->id);

    expect($signal->isDeloadNeeded())->toBeTrue();
    expect($signal->activeTriggers)->toContain('persistent_joint_pain');
});

it('nessun deload se tutti i segnali sono nella norma', function () {
    $quad = Muscle::factory()->create(['slug' => 'quadriceps', 'name_it' => 'Quad', 'muscle_group' => 'legs']);
    $squat = Exercise::factory()->create(['slug' => 'back_squat_high_bar', 'name_it' => 'Squat', 'mechanic' => 'compound']);
    attachMuscleWithPct($squat, $quad, 100);

    // Solo 3 set: volume 3 HS, ben sotto MRV=24
    $session = TrainingSession::factory()->create(['microcycle_week_id' => $this->week1->id, 'status' => 'completed']);
    $se = SessionExercise::factory()->create(['session_id' => $session->id, 'exercise_id' => $squat->id, 'planned_sets_count' => 3]);
    ExerciseSet::factory()->count(3)->create([
        'session_exercise_id' => $se->id,
        'is_warmup' => false,
        'completed_at' => now(),
        'planned_rir' => 2,
        'actual_rir' => 2,
    ]);

    $signal = $this->evaluator->evaluate($this->mesocycle->id);

    expect($signal->isDeloadNeeded())->toBeFalse();
    expect($signal->activeTriggers)->toBeEmpty();
});
