<?php

use App\Livewire\Athlete\WorkoutSession;
use App\Models\Exercise;
use App\Models\ExerciseSet;
use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\MovementPattern;
use App\Models\Muscle;
use App\Models\SessionExercise;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);

    $this->athlete = User::factory()->create();
    $this->athlete->assignRole('atleta');

    $this->trainer = User::factory()->create();
    $this->trainer->assignRole('trainer');

    $this->mesocycle = Mesocycle::factory()->create([
        'athlete_id' => $this->athlete->id,
        'trainer_id' => $this->trainer->id,
        'status' => 'active',
        'start_date' => Carbon::today(),
        'weeks_count' => 4,
    ]);

    $this->week = MicrocycleWeek::create([
        'mesocycle_id' => $this->mesocycle->id,
        'week_number' => 1,
        'is_deload' => false,
        'start_date' => Carbon::today(),
        'end_date' => Carbon::today()->addDays(6),
    ]);

    $this->session = TrainingSession::create([
        'microcycle_week_id' => $this->week->id,
        'name' => 'Push A',
        'order_in_week' => 1,
        'status' => 'planned',
        'scheduled_date' => Carbon::today(),
    ]);

    // Pattern motorio comune ai due esercizi (horizontal_push)
    $pattern = MovementPattern::create([
        'slug' => 'horizontal_push',
        'name_it' => 'Spinta orizzontale',
        'category' => 'compound_pattern',
    ]);

    $this->originalExercise = Exercise::factory()->create([
        'slug' => 'test-bench-press',
        'name_it' => 'Panca piana test',
        'mechanic' => 'compound',
        'measurement_type' => 'reps_weight',
        'compound_pattern_id' => $pattern->id,
        'joint_action_id' => null,
        'skill_level' => 'intermediate',
    ]);

    $this->substituteExercise = Exercise::factory()->create([
        'slug' => 'test-dumbbell-press',
        'name_it' => 'Panca manubri test',
        'mechanic' => 'compound',
        'measurement_type' => 'reps_weight',
        'compound_pattern_id' => $pattern->id,
        'joint_action_id' => null,
        'skill_level' => 'intermediate',
    ]);

    // Muscolo condiviso per overlap non-zero
    $muscle = Muscle::factory()->create(['slug' => 'pectoralis_major_sternal']);
    $this->originalExercise->muscles()->attach($muscle->id, ['role' => 'primary', 'contribution_pct' => 60]);
    $this->substituteExercise->muscles()->attach($muscle->id, ['role' => 'primary', 'contribution_pct' => 65]);

    $this->sessionExercise = SessionExercise::create([
        'session_id' => $this->session->id,
        'exercise_id' => $this->originalExercise->id,
        'order_in_session' => 1,
        'technique_type' => 'straight',
        'planned_sets_count' => 3,
        'planned_rest_sec' => 120,
    ]);

    for ($i = 1; $i <= 3; $i++) {
        ExerciseSet::create([
            'session_exercise_id' => $this->sessionExercise->id,
            'set_index' => $i,
            'is_warmup' => false,
            'planned_reps' => 8,
            'planned_weight_kg' => 80.0,
            'planned_rir' => 2,
        ]);
    }
});

it('confirmSubstitution aggiorna exercise_id e traccia l\'originale', function () {
    Livewire::actingAs($this->athlete)
        ->test(WorkoutSession::class, ['session' => $this->session])
        ->call('openSubstitutionModal', $this->sessionExercise->id)
        ->call('confirmSubstitution', $this->substituteExercise->slug);

    $this->sessionExercise->refresh();

    expect($this->sessionExercise->exercise_id)
        ->toBe($this->substituteExercise->id)
        ->and($this->sessionExercise->substituted_from_exercise_id)
        ->toBe($this->originalExercise->id);
});

it('i set pianificati rimangono invariati dopo la sostituzione', function () {
    $setsBefore = ExerciseSet::where('session_exercise_id', $this->sessionExercise->id)
        ->get()
        ->map(fn ($s) => [
            'set_index' => $s->set_index,
            'planned_reps' => $s->planned_reps,
            'planned_weight_kg' => (float) $s->planned_weight_kg,
            'planned_rir' => $s->planned_rir,
        ])
        ->toArray();

    Livewire::actingAs($this->athlete)
        ->test(WorkoutSession::class, ['session' => $this->session])
        ->call('openSubstitutionModal', $this->sessionExercise->id)
        ->call('confirmSubstitution', $this->substituteExercise->slug);

    $setsAfter = ExerciseSet::where('session_exercise_id', $this->sessionExercise->id)
        ->get()
        ->map(fn ($s) => [
            'set_index' => $s->set_index,
            'planned_reps' => $s->planned_reps,
            'planned_weight_kg' => (float) $s->planned_weight_kg,
            'planned_rir' => $s->planned_rir,
        ])
        ->toArray();

    expect($setsAfter)->toBe($setsBefore);
});

it('la sostituzione è bloccata se almeno un set working è già completato', function () {
    // Completa il primo set
    ExerciseSet::where('session_exercise_id', $this->sessionExercise->id)
        ->where('set_index', 1)
        ->update(['completed_at' => now(), 'actual_reps' => 8, 'actual_weight_kg' => 80]);

    Livewire::actingAs($this->athlete)
        ->test(WorkoutSession::class, ['session' => $this->session])
        ->call('openSubstitutionModal', $this->sessionExercise->id)
        ->call('confirmSubstitution', $this->substituteExercise->slug);

    $this->sessionExercise->refresh();

    // exercise_id deve essere rimasto l'originale
    expect($this->sessionExercise->exercise_id)->toBe($this->originalExercise->id)
        ->and($this->sessionExercise->substituted_from_exercise_id)->toBeNull();
});

it('openSubstitutionModal popola substitutionCandidates', function () {
    $component = Livewire::actingAs($this->athlete)
        ->test(WorkoutSession::class, ['session' => $this->session])
        ->call('openSubstitutionModal', $this->sessionExercise->id);

    $candidates = $component->get('substitutionCandidates');

    expect($candidates)->toBeArray()
        ->and($candidates)->not->toBeEmpty();

    $slugs = array_column($candidates, 'slug');
    expect($slugs)->toContain($this->substituteExercise->slug);
});

it('closeSubstitutionModal azzera lo stato sostituzione', function () {
    $component = Livewire::actingAs($this->athlete)
        ->test(WorkoutSession::class, ['session' => $this->session])
        ->call('openSubstitutionModal', $this->sessionExercise->id)
        ->call('closeSubstitutionModal');

    expect($component->get('substitutingSeId'))->toBeNull()
        ->and($component->get('substitutionCandidates'))->toBe([]);
});
