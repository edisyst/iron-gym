<?php

use App\Models\Exercise;
use App\Models\ExerciseSet;
use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\PersonalRecord;
use App\Models\SessionExercise;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\PersonalRecordDetector;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    config(['pr.max_reps_epley' => 12, 'pr.min_sessions_before_pr' => 3]);
});

/**
 * Crea atleta con N sessioni completate su un esercizio.
 * Restituisce ['athlete', 'exercise', 'set'] con l'ultimo set pronto per il check.
 *
 * @return array{athlete: User, exercise: Exercise, set: ExerciseSet}
 */
function makeAthleteWithHistory(int $completedSessions = 3, array $lastSetOverrides = []): array
{
    $athlete = User::factory()->create();
    $athlete->assignRole('atleta');

    $mesocycle = Mesocycle::factory()->active()->create(['athlete_id' => $athlete->id]);
    $exercise = Exercise::factory()->create(['measurement_type' => 'reps_weight']);

    $lastSet = null;

    for ($i = 0; $i < $completedSessions; $i++) {
        $week = MicrocycleWeek::factory()->create(['mesocycle_id' => $mesocycle->id, 'week_number' => $i + 1]);
        $session = TrainingSession::factory()->completed()->create(['microcycle_week_id' => $week->id]);
        $se = SessionExercise::factory()->create([
            'session_id' => $session->id,
            'exercise_id' => $exercise->id,
        ]);
        $set = ExerciseSet::factory()->create(array_merge([
            'session_exercise_id' => $se->id,
            'is_warmup' => false,
            'actual_reps' => 8,
            'actual_weight_kg' => 80.0,
            'completed_at' => now(),
        ], $i === $completedSessions - 1 ? $lastSetOverrides : []));

        $lastSet = $set;
    }

    return ['athlete' => $athlete, 'exercise' => $exercise, 'set' => $lastSet];
}

it('registra PR dopo la soglia minima di sessioni', function () {
    ['athlete' => $athlete, 'set' => $set] = makeAthleteWithHistory(3, [
        'actual_reps' => 8,
        'actual_weight_kg' => 100.0,
    ]);

    $set->load('sessionExercise.exercise');

    $pr = app(PersonalRecordDetector::class)->check($set, $athlete->id);

    expect($pr)->not->toBeNull()
        ->and($pr->record_type)->toBe('e1rm')
        ->and($pr->value)->toBeGreaterThan(0.0)
        ->and(PersonalRecord::count())->toBe(1);
});

it('non registra PR se e1RM non supera il record precedente', function () {
    ['athlete' => $athlete, 'exercise' => $exercise, 'set' => $set] = makeAthleteWithHistory(3, [
        'actual_reps' => 8,
        'actual_weight_kg' => 80.0,
    ]);

    PersonalRecord::create([
        'athlete_id' => $athlete->id,
        'exercise_id' => $exercise->id,
        'exercise_set_id' => $set->id,
        'record_type' => 'e1rm',
        'value' => 999.00,
        'achieved_at' => now()->subDays(10),
    ]);

    $set->load('sessionExercise.exercise');

    $result = app(PersonalRecordDetector::class)->check($set, $athlete->id);

    expect($result)->toBeNull()
        ->and(PersonalRecord::count())->toBe(1);
});

it('ignora set warmup', function () {
    ['athlete' => $athlete, 'set' => $set] = makeAthleteWithHistory(3);

    $set->update(['is_warmup' => true]);
    $set->refresh();
    $set->load('sessionExercise.exercise');

    $result = app(PersonalRecordDetector::class)->check($set, $athlete->id);

    expect($result)->toBeNull()
        ->and(PersonalRecord::count())->toBe(0);
});

it('ignora set con reps oltre soglia config', function () {
    ['athlete' => $athlete, 'set' => $set] = makeAthleteWithHistory(3, [
        'actual_reps' => 15,
        'actual_weight_kg' => 60.0,
    ]);

    $set->load('sessionExercise.exercise');

    $result = app(PersonalRecordDetector::class)->check($set, $athlete->id);

    expect($result)->toBeNull()
        ->and(PersonalRecord::count())->toBe(0);
});

it('ignora misurazioni non reps_weight', function () {
    $athlete = User::factory()->create();
    $athlete->assignRole('atleta');

    $mesocycle = Mesocycle::factory()->active()->create(['athlete_id' => $athlete->id]);
    $exercise = Exercise::factory()->create(['measurement_type' => 'reps_only']);

    for ($i = 0; $i < 3; $i++) {
        $week = MicrocycleWeek::factory()->create(['mesocycle_id' => $mesocycle->id, 'week_number' => $i + 1]);
        $session = TrainingSession::factory()->completed()->create(['microcycle_week_id' => $week->id]);
        $se = SessionExercise::factory()->create([
            'session_id' => $session->id,
            'exercise_id' => $exercise->id,
        ]);
        $set = ExerciseSet::factory()->create([
            'session_exercise_id' => $se->id,
            'is_warmup' => false,
            'actual_reps' => 10,
            'actual_weight_kg' => null,
            'completed_at' => now(),
        ]);
    }

    $set->load('sessionExercise.exercise');

    $result = app(PersonalRecordDetector::class)->check($set, $athlete->id);

    expect($result)->toBeNull()
        ->and(PersonalRecord::count())->toBe(0);
});

it('non registra PR prima della soglia minima di sessioni', function () {
    ['athlete' => $athlete, 'set' => $set] = makeAthleteWithHistory(2, [
        'actual_reps' => 8,
        'actual_weight_kg' => 100.0,
    ]);

    $set->load('sessionExercise.exercise');

    $result = app(PersonalRecordDetector::class)->check($set, $athlete->id);

    expect($result)->toBeNull()
        ->and(PersonalRecord::count())->toBe(0);
});
