<?php

use App\Livewire\Athlete\WorkoutSession;
use App\Models\Exercise;
use App\Models\ExerciseSet;
use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
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

    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    $mesocycle = Mesocycle::factory()->create([
        'athlete_id' => $this->athlete->id,
        'trainer_id' => $trainer->id,
        'status' => 'active',
        'start_date' => Carbon::today(),
        'weeks_count' => 4,
    ]);

    $week = MicrocycleWeek::create([
        'mesocycle_id' => $mesocycle->id,
        'week_number' => 1,
        'is_deload' => false,
        'start_date' => Carbon::today(),
        'end_date' => Carbon::today()->addDays(6),
    ]);

    $this->session = TrainingSession::create([
        'microcycle_week_id' => $week->id,
        'name' => 'Push A',
        'order_in_week' => 1,
        'status' => 'in_progress',
        'scheduled_date' => Carbon::today(),
        'started_at' => now(),
    ]);

    // Tre esercizi separati (senza gruppo)
    for ($i = 1; $i <= 3; $i++) {
        $ex = Exercise::factory()->create(['measurement_type' => 'reps_weight']);
        $se = SessionExercise::create([
            'session_id' => $this->session->id,
            'exercise_id' => $ex->id,
            'order_in_session' => $i,
            'technique_type' => 'straight',
            'planned_sets_count' => 3,
            'planned_rest_sec' => 120,
        ]);
        ExerciseSet::create([
            'session_exercise_id' => $se->id,
            'set_index' => 1,
            'is_warmup' => false,
            'planned_reps' => 10,
            'planned_rir' => 2,
        ]);
    }
});

test('navigazione avanti incrementa currentGroupIndex', function () {
    $component = Livewire::actingAs($this->athlete)
        ->test(WorkoutSession::class, ['session' => $this->session]);

    $component->assertSet('currentGroupIndex', 0);
    $component->call('nextGroup');
    $component->assertSet('currentGroupIndex', 1);
    $component->call('nextGroup');
    $component->assertSet('currentGroupIndex', 2);
});

test('navigazione indietro decrementa currentGroupIndex', function () {
    $component = Livewire::actingAs($this->athlete)
        ->test(WorkoutSession::class, ['session' => $this->session]);

    $component->call('nextGroup');
    $component->call('nextGroup');
    $component->assertSet('currentGroupIndex', 2);

    $component->call('prevGroup');
    $component->assertSet('currentGroupIndex', 1);
});

test('prevGroup non scende sotto zero', function () {
    $component = Livewire::actingAs($this->athlete)
        ->test(WorkoutSession::class, ['session' => $this->session]);

    $component->assertSet('currentGroupIndex', 0);
    $component->call('prevGroup');
    $component->assertSet('currentGroupIndex', 0);
});

test('nextGroup non supera il totale dei gruppi', function () {
    $component = Livewire::actingAs($this->athlete)
        ->test(WorkoutSession::class, ['session' => $this->session]);

    $component->call('nextGroup');
    $component->call('nextGroup');
    $component->call('nextGroup');
    $component->assertSet('currentGroupIndex', 2);
});

test('jumpToGroup salta al gruppo specificato', function () {
    $component = Livewire::actingAs($this->athlete)
        ->test(WorkoutSession::class, ['session' => $this->session]);

    $component->call('jumpToGroup', 2);
    $component->assertSet('currentGroupIndex', 2);
});

test('jumpToGroup ignora indici fuori range', function () {
    $component = Livewire::actingAs($this->athlete)
        ->test(WorkoutSession::class, ['session' => $this->session]);

    $component->call('jumpToGroup', 99);
    $component->assertSet('currentGroupIndex', 0);

    $component->call('jumpToGroup', -1);
    $component->assertSet('currentGroupIndex', 0);
});

test('setData è pre-compilato con valori pianificati per set non completati', function () {
    $component = Livewire::actingAs($this->athlete)
        ->test(WorkoutSession::class, ['session' => $this->session]);

    $set = $this->session->sessionExercises->first()->sets->first();

    expect($component->get('setData')[$set->id]['reps'])->toBe('10');
    expect($component->get('setData')[$set->id]['rir'])->toBe('2');
});
