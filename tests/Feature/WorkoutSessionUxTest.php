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
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeSessionWithExercise(User $athlete, array $exerciseAttrs = [], array $seAttrs = []): array
{
    $trainer = User::factory()->create();

    $mesocycle = Mesocycle::factory()->create([
        'athlete_id' => $athlete->id,
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

    $session = TrainingSession::create([
        'microcycle_week_id' => $week->id,
        'name' => 'Push A',
        'order_in_week' => 1,
        'status' => 'planned',
        'scheduled_date' => Carbon::today(),
    ]);

    $exercise = Exercise::factory()->create(array_merge(['measurement_type' => 'reps_weight'], $exerciseAttrs));

    $se = SessionExercise::create(array_merge([
        'session_id' => $session->id,
        'exercise_id' => $exercise->id,
        'order_in_session' => 1,
        'technique_type' => 'straight',
        'planned_sets_count' => 3,
        'planned_rest_sec' => 120,
        'intra_cluster_rest_sec' => null,
    ], $seAttrs));

    return compact('session', 'exercise', 'se', 'week', 'mesocycle');
}

function makeWorkingSet(SessionExercise $se, int $index, array $attrs = []): ExerciseSet
{
    return ExerciseSet::create(array_merge([
        'session_exercise_id' => $se->id,
        'set_index' => $index,
        'is_warmup' => false,
        'planned_reps' => 8,
        'planned_weight_kg' => 100,
        'planned_rir' => 2,
        'planned_duration_sec' => null,
        'completed_at' => null,
    ], $attrs));
}

beforeEach(function () {
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);

    $this->athlete = User::factory()->create();
    $this->athlete->assignRole('atleta');
    $this->actingAs($this->athlete);
});

// ---------------------------------------------------------------------------
// Fase B — Quick-log
// ---------------------------------------------------------------------------

it('quickLog copia planned_reps/weight/rir su set reps_weight', function () {
    ['session' => $session, 'se' => $se] = makeSessionWithExercise($this->athlete);
    $set = makeWorkingSet($se, 1);

    Livewire::test(WorkoutSession::class, ['session' => $session])
        ->call('quickLog', $set->id);

    $updated = ExerciseSet::find($set->id);
    expect($updated->actual_reps)->toBe(8)
        ->and((float) $updated->actual_weight_kg)->toBe(100.0)
        ->and($updated->actual_rir)->toBe(2)
        ->and($updated->completed_at)->not->toBeNull();
});

it('quickLog su reps_only non copia weight', function () {
    ['session' => $session, 'se' => $se] = makeSessionWithExercise(
        $this->athlete,
        ['measurement_type' => 'reps_only']
    );
    $set = makeWorkingSet($se, 1, ['planned_weight_kg' => null]);

    Livewire::test(WorkoutSession::class, ['session' => $session])
        ->call('quickLog', $set->id);

    $updated = ExerciseSet::find($set->id);
    expect($updated->actual_reps)->toBe(8)
        ->and($updated->actual_weight_kg)->toBeNull()
        ->and($updated->completed_at)->not->toBeNull();
});

it('quickLog su isometric_hold copia solo duration', function () {
    ['session' => $session, 'se' => $se] = makeSessionWithExercise(
        $this->athlete,
        ['measurement_type' => 'isometric_hold']
    );
    $set = makeWorkingSet($se, 1, [
        'planned_reps' => null,
        'planned_weight_kg' => null,
        'planned_rir' => null,
        'planned_duration_sec' => 45,
    ]);

    Livewire::test(WorkoutSession::class, ['session' => $session])
        ->call('quickLog', $set->id);

    $updated = ExerciseSet::find($set->id);
    expect($updated->actual_duration_sec)->toBe(45)
        ->and($updated->actual_reps)->toBeNull()
        ->and($updated->actual_weight_kg)->toBeNull()
        ->and($updated->completed_at)->not->toBeNull();
});

it('quickLog non resetta completed_at se già valorizzato', function () {
    ['session' => $session, 'se' => $se] = makeSessionWithExercise($this->athlete);
    $originalTime = Carbon::now()->subMinutes(5);
    $set = makeWorkingSet($se, 1, ['completed_at' => $originalTime]);

    Livewire::test(WorkoutSession::class, ['session' => $session])
        ->call('quickLog', $set->id);

    $updated = ExerciseSet::find($set->id);
    expect($updated->completed_at->toDateTimeString())
        ->toBe($originalTime->toDateTimeString());
});

it('completeSet non resetta completed_at se già valorizzato', function () {
    ['session' => $session, 'se' => $se] = makeSessionWithExercise($this->athlete);
    $originalTime = Carbon::now()->subMinutes(10);
    $set = makeWorkingSet($se, 1, [
        'actual_reps' => 8,
        'actual_weight_kg' => 100,
        'actual_rir' => 2,
        'completed_at' => $originalTime,
    ]);

    Livewire::test(WorkoutSession::class, ['session' => $session])
        ->set("setData.{$set->id}.reps", '9')
        ->set("setData.{$set->id}.weight", '102.5')
        ->call('completeSet', $set->id);

    $updated = ExerciseSet::find($set->id);
    expect($updated->actual_reps)->toBe(9)
        ->and((float) $updated->actual_weight_kg)->toBe(102.5)
        ->and($updated->completed_at->toDateTimeString())
        ->toBe($originalTime->toDateTimeString());
});

// ---------------------------------------------------------------------------
// Fase C — Previous performance
// ---------------------------------------------------------------------------

it('previousPerformance viene popolato dalla sessione precedente', function () {
    ['session' => $session, 'se' => $se, 'exercise' => $exercise, 'week' => $week] =
        makeSessionWithExercise($this->athlete);
    makeWorkingSet($se, 1);

    // Sessione precedente completata
    $prevSession = TrainingSession::create([
        'microcycle_week_id' => $week->id,
        'name' => 'Push A prev',
        'order_in_week' => 1,
        'status' => 'completed',
        'scheduled_date' => Carbon::yesterday(),
        'started_at' => Carbon::yesterday()->addHour(),
        'completed_at' => Carbon::yesterday()->addHours(2),
    ]);

    $prevSe = SessionExercise::create([
        'session_id' => $prevSession->id,
        'exercise_id' => $exercise->id,
        'order_in_session' => 1,
        'technique_type' => 'straight',
        'planned_sets_count' => 3,
        'planned_rest_sec' => 120,
    ]);

    ExerciseSet::create([
        'session_exercise_id' => $prevSe->id,
        'set_index' => 1,
        'is_warmup' => false,
        'planned_reps' => 8,
        'planned_weight_kg' => 80,
        'actual_reps' => 8,
        'actual_weight_kg' => 80,
        'actual_rir' => 2,
        'completed_at' => Carbon::yesterday()->addHours(2),
    ]);

    $component = Livewire::test(WorkoutSession::class, ['session' => $session]);

    $prev = $component->instance()->previousPerformance;

    expect($prev)->toHaveKey($exercise->id)
        ->and($prev[$exercise->id])->toHaveKey(1)
        ->and($prev[$exercise->id][1]['reps'])->toBe(8)
        ->and((float) $prev[$exercise->id][1]['weight'])->toBe(80.0)
        ->and($prev[$exercise->id][1]['rir'])->toBe(2);
});

it('previousPerformance è vuoto senza sessioni precedenti', function () {
    ['session' => $session, 'se' => $se] = makeSessionWithExercise($this->athlete);
    makeWorkingSet($se, 1);

    $component = Livewire::test(WorkoutSession::class, ['session' => $session]);

    expect($component->instance()->previousPerformance)->toBeEmpty();
});

it('previousPerformance esclude i warm-up dalla sessione precedente', function () {
    ['session' => $session, 'se' => $se, 'exercise' => $exercise, 'week' => $week] =
        makeSessionWithExercise($this->athlete);
    makeWorkingSet($se, 1);

    $prevSession = TrainingSession::create([
        'microcycle_week_id' => $week->id,
        'name' => 'Push A prev',
        'order_in_week' => 1,
        'status' => 'completed',
        'scheduled_date' => Carbon::yesterday(),
        'started_at' => Carbon::yesterday()->addHour(),
        'completed_at' => Carbon::yesterday()->addHours(2),
    ]);

    $prevSe = SessionExercise::create([
        'session_id' => $prevSession->id,
        'exercise_id' => $exercise->id,
        'order_in_session' => 1,
        'technique_type' => 'straight',
        'planned_sets_count' => 1,
        'planned_rest_sec' => 120,
    ]);

    // Solo un set warmup (deve essere escluso dalla previous performance)
    ExerciseSet::create([
        'session_exercise_id' => $prevSe->id,
        'set_index' => 1,
        'is_warmup' => true,
        'planned_reps' => 8,
        'actual_reps' => 8,
        'actual_weight_kg' => 50,
        'completed_at' => Carbon::yesterday()->addHours(2),
    ]);

    $component = Livewire::test(WorkoutSession::class, ['session' => $session]);
    $prev = $component->instance()->previousPerformance;

    // L'esercizio non ha set working → nessuna entry nel prev
    expect($prev)->not->toHaveKey($exercise->id);
});

// ---------------------------------------------------------------------------
// Fase E — Warm-up generator
// ---------------------------------------------------------------------------

it('generateWarmup crea 3 set per target >= 40 kg', function () {
    ['session' => $session, 'se' => $se] = makeSessionWithExercise($this->athlete);
    makeWorkingSet($se, 1, ['planned_weight_kg' => 100]);

    Livewire::test(WorkoutSession::class, ['session' => $session])
        ->call('generateWarmup', $se->id);

    $warmup = ExerciseSet::where('session_exercise_id', $se->id)
        ->where('is_warmup', true)
        ->orderBy('set_index')
        ->get();

    expect($warmup)->toHaveCount(3)
        ->and((float) $warmup[0]->planned_weight_kg)->toBe(50.0)  // 50% di 100
        ->and($warmup[0]->planned_reps)->toBe(8)
        ->and((float) $warmup[1]->planned_weight_kg)->toBe(70.0)  // 70% di 100
        ->and($warmup[1]->planned_reps)->toBe(5)
        ->and((float) $warmup[2]->planned_weight_kg)->toBe(85.0)  // 85% di 100
        ->and($warmup[2]->planned_reps)->toBe(3);
});

it('generateWarmup crea solo 1 set per target < 40 kg', function () {
    ['session' => $session, 'se' => $se] = makeSessionWithExercise($this->athlete);
    makeWorkingSet($se, 1, ['planned_weight_kg' => 30]);

    Livewire::test(WorkoutSession::class, ['session' => $session])
        ->call('generateWarmup', $se->id);

    $warmup = ExerciseSet::where('session_exercise_id', $se->id)
        ->where('is_warmup', true)
        ->get();

    expect($warmup)->toHaveCount(1)
        ->and((float) $warmup->first()->planned_weight_kg)->toBe(15.0);  // 50% di 30
});

it('generateWarmup arrotonda a 2.5 kg', function () {
    ['session' => $session, 'se' => $se] = makeSessionWithExercise($this->athlete);
    // 83 kg → 50%=41.5→42.5, 70%=58.1→57.5, 85%=70.55→70
    makeWorkingSet($se, 1, ['planned_weight_kg' => 83]);

    Livewire::test(WorkoutSession::class, ['session' => $session])
        ->call('generateWarmup', $se->id);

    $warmup = ExerciseSet::where('session_exercise_id', $se->id)
        ->where('is_warmup', true)
        ->orderBy('set_index')
        ->get();

    $warmup->each(function ($set) {
        $remainder = fmod((float) $set->planned_weight_kg, 2.5);
        expect(round($remainder, 6))->toBe(0.0);
    });
});

it('generateWarmup è idempotente', function () {
    ['session' => $session, 'se' => $se] = makeSessionWithExercise($this->athlete);
    makeWorkingSet($se, 1, ['planned_weight_kg' => 100]);

    $component = Livewire::test(WorkoutSession::class, ['session' => $session]);
    $component->call('generateWarmup', $se->id);
    $component->call('generateWarmup', $se->id);

    $warmupCount = ExerciseSet::where('session_exercise_id', $se->id)
        ->where('is_warmup', true)
        ->count();

    expect($warmupCount)->toBe(3);
});

it('generateWarmup non genera set se planned_weight_kg è null', function () {
    ['session' => $session, 'se' => $se] = makeSessionWithExercise(
        $this->athlete,
        ['measurement_type' => 'reps_only']
    );
    makeWorkingSet($se, 1, ['planned_weight_kg' => null]);

    Livewire::test(WorkoutSession::class, ['session' => $session])
        ->call('generateWarmup', $se->id);

    $warmupCount = ExerciseSet::where('session_exercise_id', $se->id)
        ->where('is_warmup', true)
        ->count();

    expect($warmupCount)->toBe(0);
});

it('deleteWarmupSet rimuove il set warmup', function () {
    ['session' => $session, 'se' => $se] = makeSessionWithExercise($this->athlete);
    makeWorkingSet($se, 2, ['planned_weight_kg' => 100]);

    $warmupSet = ExerciseSet::create([
        'session_exercise_id' => $se->id,
        'set_index' => 1,
        'is_warmup' => true,
        'planned_reps' => 8,
        'planned_weight_kg' => 50,
    ]);

    Livewire::test(WorkoutSession::class, ['session' => $session])
        ->call('deleteWarmupSet', $warmupSet->id);

    expect(ExerciseSet::find($warmupSet->id))->toBeNull();
});

it('deleteWarmupSet rifiuta di cancellare un working set', function () {
    ['session' => $session, 'se' => $se] = makeSessionWithExercise($this->athlete);
    $workingSet = makeWorkingSet($se, 1);

    expect(fn () => Livewire::test(WorkoutSession::class, ['session' => $session])
        ->call('deleteWarmupSet', $workingSet->id)
    )->toThrow(ModelNotFoundException::class);
});
