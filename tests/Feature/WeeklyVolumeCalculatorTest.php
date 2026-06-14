<?php

use App\Models\Exercise;
use App\Models\ExerciseSet;
use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\Muscle;
use App\Models\SessionExercise;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\WeeklyVolumeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed minimo: muscolo e esercizio
    $this->sternal = Muscle::factory()->create([
        'slug' => 'pectoralis_major_sternal',
        'name_it' => 'Gran pettorale (sternale)',
        'muscle_group' => 'chest',
        'muscle_head' => 'sternal',
    ]);
    $this->triceps = Muscle::factory()->create([
        'slug' => 'triceps_brachii',
        'name_it' => 'Tricipite brachiale',
        'muscle_group' => 'arms',
    ]);

    $this->exercise = Exercise::factory()->create([
        'slug' => 'barbell_bench_press',
        'name_it' => 'Panca piana con bilanciere',
        'mechanic' => 'compound',
    ]);

    $this->exercise->muscles()->attach([
        $this->sternal->id => ['role' => 'primary',   'contribution_pct' => 60],
        $this->triceps->id => ['role' => 'secondary',  'contribution_pct' => 20],
    ]);

    // Struttura mesociclo
    $athlete = User::factory()->create();
    $trainer = User::factory()->create();

    $this->mesocycle = Mesocycle::factory()->create([
        'athlete_id' => $athlete->id,
        'trainer_id' => $trainer->id,
    ]);
    $this->week = MicrocycleWeek::factory()->create([
        'mesocycle_id' => $this->mesocycle->id,
        'week_number' => 1,
    ]);

    $this->athleteId = $athlete->id;
    $this->calc = app(WeeklyVolumeCalculator::class);
});

it('un set di panca piana contribuisce 0.60 hard set al pettorale sternale', function () {
    $session = TrainingSession::factory()->create([
        'microcycle_week_id' => $this->week->id,
        'status' => 'completed',
    ]);
    $se = SessionExercise::factory()->create([
        'session_id' => $session->id,
        'exercise_id' => $this->exercise->id,
        'planned_sets_count' => 1,
    ]);
    ExerciseSet::factory()->create([
        'session_exercise_id' => $se->id,
        'is_warmup' => false,
        'completed_at' => now(),
    ]);

    $result = $this->calc->calculate($this->athleteId, $this->week->id);

    expect($result['pectoralis_major_sternal']['hard_sets'])->toBe(0.6);
    expect($result['triceps_brachii']['hard_sets'])->toBe(0.2);
});

it('i set warmup non vengono contati nel volume', function () {
    $session = TrainingSession::factory()->create([
        'microcycle_week_id' => $this->week->id,
        'status' => 'completed',
    ]);
    $se = SessionExercise::factory()->create([
        'session_id' => $session->id,
        'exercise_id' => $this->exercise->id,
        'planned_sets_count' => 2,
    ]);
    // Un set warmup + un set working
    ExerciseSet::factory()->create([
        'session_exercise_id' => $se->id,
        'is_warmup' => true,
        'completed_at' => now(),
    ]);
    ExerciseSet::factory()->create([
        'session_exercise_id' => $se->id,
        'is_warmup' => false,
        'completed_at' => now(),
    ]);

    $result = $this->calc->calculate($this->athleteId, $this->week->id);

    // Solo 1 working set: 0.60 per il pettorale
    expect($result['pectoralis_major_sternal']['hard_sets'])->toBe(0.6);
});

it('il volume è zero se non ci sono sessioni completed', function () {
    // Sessione planned, non completed
    TrainingSession::factory()->create([
        'microcycle_week_id' => $this->week->id,
        'status' => 'planned',
    ]);

    $result = $this->calc->calculate($this->athleteId, $this->week->id);

    // Nessuna sessione completed → nessun muscolo con hard_sets > 0
    foreach ($result as $data) {
        expect($data['hard_sets'])->toBe(0.0);
    }
});
