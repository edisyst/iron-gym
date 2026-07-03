<?php

use App\Models\ExerciseSet;
use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\Muscle;
use App\Models\PersonalRecord;
use App\Models\SessionExercise;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\SessionRecapBuilder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

/**
 * Crea atleta + sessione completata vuota.
 *
 * @return array{athlete: User, session: TrainingSession}
 */
function makeSession(?string $startedAt = null, ?string $completedAt = null): array
{
    $athlete = User::factory()->create();
    $athlete->assignRole('atleta');

    $mesocycle = Mesocycle::factory()->active()->create(['athlete_id' => $athlete->id]);
    $week = MicrocycleWeek::factory()->create(['mesocycle_id' => $mesocycle->id]);

    $session = TrainingSession::factory()->create([
        'microcycle_week_id' => $week->id,
        'status' => 'completed',
        'started_at' => $startedAt ?? now()->subHour()->toDateTimeString(),
        'completed_at' => $completedAt ?? now()->toDateTimeString(),
    ]);

    return ['athlete' => $athlete, 'session' => $session];
}

/**
 * Crea un exercise_id di comodo inserendo direttamente la riga (SQLite non impone CHECK XOR).
 */
function insertExercise(string $slug = 'test-exercise'): int
{
    return DB::table('exercises')->insertGetId([
        'slug' => $slug,
        'name_it' => 'Esercizio '.$slug,
        'mechanic' => 'compound',
        'plane' => 'sagittal',
        'laterality' => 'bilateral',
        'skill_level' => 'beginner',
        'measurement_type' => 'reps_weight',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// ---- Tonnellaggio ----

it('calcola tonnellaggio escludendo i set warmup', function () {
    ['athlete' => $athlete, 'session' => $session] = makeSession();

    $exerciseId = insertExercise('ex-tonnage');
    $se = SessionExercise::factory()->create([
        'session_id' => $session->id,
        'exercise_id' => $exerciseId,
    ]);

    // Warmup completato: deve essere escluso
    ExerciseSet::factory()->create([
        'session_exercise_id' => $se->id,
        'is_warmup' => true,
        'actual_reps' => 8,
        'actual_weight_kg' => 40.0,
        'completed_at' => now(),
    ]);

    // Due working set completati: 80×8 + 80×8 = 1280
    ExerciseSet::factory()->create([
        'session_exercise_id' => $se->id,
        'is_warmup' => false,
        'actual_reps' => 8,
        'actual_weight_kg' => 80.0,
        'completed_at' => now(),
    ]);
    ExerciseSet::factory()->create([
        'session_exercise_id' => $se->id,
        'is_warmup' => false,
        'actual_reps' => 8,
        'actual_weight_kg' => 80.0,
        'completed_at' => now(),
    ]);

    $session->refresh();
    $recap = app(SessionRecapBuilder::class)->build($session, $athlete->id);

    expect($recap['tonnage_kg'])->toBe(1280.0);
});

it('esclude set non completati dal tonnellaggio', function () {
    ['athlete' => $athlete, 'session' => $session] = makeSession();

    $exerciseId = insertExercise('ex-partial-tonnage');
    $se = SessionExercise::factory()->create([
        'session_id' => $session->id,
        'exercise_id' => $exerciseId,
    ]);

    // Set completato: 100×5 = 500
    ExerciseSet::factory()->create([
        'session_exercise_id' => $se->id,
        'is_warmup' => false,
        'actual_reps' => 5,
        'actual_weight_kg' => 100.0,
        'completed_at' => now(),
    ]);

    // Set non completato: non conta
    ExerciseSet::factory()->create([
        'session_exercise_id' => $se->id,
        'is_warmup' => false,
        'actual_reps' => 5,
        'actual_weight_kg' => 100.0,
        'completed_at' => null,
    ]);

    $session->refresh();
    $recap = app(SessionRecapBuilder::class)->build($session, $athlete->id);

    expect($recap['tonnage_kg'])->toBe(500.0);
});

// ---- Set completati vs prescritti ----

it('conta set completati e prescritti escludendo warmup', function () {
    ['athlete' => $athlete, 'session' => $session] = makeSession();

    $exerciseId = insertExercise('ex-counts');
    $se = SessionExercise::factory()->create([
        'session_id' => $session->id,
        'exercise_id' => $exerciseId,
    ]);

    // 1 warmup (non conta in prescribed)
    ExerciseSet::factory()->create([
        'session_exercise_id' => $se->id,
        'is_warmup' => true,
        'completed_at' => now(),
    ]);

    // 3 working set: 2 completati, 1 no
    ExerciseSet::factory()->create([
        'session_exercise_id' => $se->id,
        'is_warmup' => false,
        'completed_at' => now(),
    ]);
    ExerciseSet::factory()->create([
        'session_exercise_id' => $se->id,
        'is_warmup' => false,
        'completed_at' => now(),
    ]);
    ExerciseSet::factory()->create([
        'session_exercise_id' => $se->id,
        'is_warmup' => false,
        'completed_at' => null,
    ]);

    $session->refresh();
    $recap = app(SessionRecapBuilder::class)->build($session, $athlete->id);

    expect($recap['sets_completed'])->toBe(2)
        ->and($recap['sets_prescribed'])->toBe(3);
});

// ---- PR ----

it('restituisce zero PR se nessun record nella sessione', function () {
    ['athlete' => $athlete, 'session' => $session] = makeSession();

    $exerciseId = insertExercise('ex-no-pr');
    $mesocycle = Mesocycle::factory()->active()->create(['athlete_id' => $athlete->id]);
    $week = MicrocycleWeek::factory()->create(['mesocycle_id' => $mesocycle->id]);
    $otherSes = TrainingSession::factory()->create(['microcycle_week_id' => $week->id, 'status' => 'completed']);
    $se = SessionExercise::factory()->create(['session_id' => $otherSes->id, 'exercise_id' => $exerciseId]);
    $set = ExerciseSet::factory()->create(['session_exercise_id' => $se->id, 'is_warmup' => false]);

    // PR con achieved_at fuori dal range della sessione corrente
    PersonalRecord::create([
        'athlete_id' => $athlete->id,
        'exercise_id' => $exerciseId,
        'exercise_set_id' => $set->id,
        'record_type' => 'e1rm',
        'value' => 120.0,
        'achieved_at' => now()->subDays(5),
    ]);

    $session->refresh();
    $recap = app(SessionRecapBuilder::class)->build($session, $athlete->id);

    expect($recap['prs'])->toHaveCount(0);
});

it('restituisce i PR ottenuti nel range della sessione', function () {
    ['athlete' => $athlete, 'session' => $session] = makeSession(
        startedAt: now()->subMinutes(90)->toDateTimeString(),
        completedAt: now()->toDateTimeString()
    );

    $exerciseId = insertExercise('ex-with-pr');

    $se = SessionExercise::factory()->create([
        'session_id' => $session->id,
        'exercise_id' => $exerciseId,
    ]);
    $set = ExerciseSet::factory()->create([
        'session_exercise_id' => $se->id,
        'is_warmup' => false,
        'completed_at' => now()->subMinutes(30),
    ]);

    PersonalRecord::create([
        'athlete_id' => $athlete->id,
        'exercise_id' => $exerciseId,
        'exercise_set_id' => $set->id,
        'record_type' => 'e1rm',
        'value' => 135.0,
        'achieved_at' => now()->subMinutes(30),
    ]);

    $session->refresh();
    $recap = app(SessionRecapBuilder::class)->build($session, $athlete->id);

    expect($recap['prs'])->toHaveCount(1);
});

// ---- Top muscoli ----

it('ordina i top muscoli per score pesato su contribution_pct', function () {
    ['athlete' => $athlete, 'session' => $session] = makeSession();

    // Esercizio A: petto 70% + spalle 30%
    $exA = insertExercise('ex-muscle-a');
    // Esercizio B: schiena 90% + bicipiti 10%
    $exB = insertExercise('ex-muscle-b');

    $petto = Muscle::factory()->create(['name_it' => 'Petto',    'slug' => 'petto']);
    $spalle = Muscle::factory()->create(['name_it' => 'Spalle',   'slug' => 'spalle']);
    $schiena = Muscle::factory()->create(['name_it' => 'Schiena',  'slug' => 'schiena']);
    $bicipiti = Muscle::factory()->create(['name_it' => 'Bicipiti', 'slug' => 'bicipiti']);

    DB::table('exercise_muscle')->insert([
        ['exercise_id' => $exA, 'muscle_id' => $petto->id,    'role' => 'primary',   'contribution_pct' => 70],
        ['exercise_id' => $exA, 'muscle_id' => $spalle->id,   'role' => 'secondary', 'contribution_pct' => 30],
        ['exercise_id' => $exB, 'muscle_id' => $schiena->id,  'role' => 'primary',   'contribution_pct' => 90],
        ['exercise_id' => $exB, 'muscle_id' => $bicipiti->id, 'role' => 'secondary', 'contribution_pct' => 10],
    ]);

    $seA = SessionExercise::factory()->create(['session_id' => $session->id, 'exercise_id' => $exA]);
    $seB = SessionExercise::factory()->create(['session_id' => $session->id, 'exercise_id' => $exB]);

    // 3 set completati per A (petto score: 70×3=210, spalle: 30×3=90)
    // 1 set completato per B  (schiena: 90×1=90, bicipiti: 10×1=10)
    for ($i = 0; $i < 3; $i++) {
        ExerciseSet::factory()->create([
            'session_exercise_id' => $seA->id,
            'is_warmup' => false,
            'completed_at' => now(),
        ]);
    }
    ExerciseSet::factory()->create([
        'session_exercise_id' => $seB->id,
        'is_warmup' => false,
        'completed_at' => now(),
    ]);

    $session->refresh();
    $recap = app(SessionRecapBuilder::class)->build($session, $athlete->id);

    $slugs = array_column($recap['top_muscles'], 'slug');

    // Petto (210) > Schiena (90) = Spalle (90) tie-break per id → dipende dall'ordine, ma petto è #1
    expect($recap['top_muscles'])->toHaveCount(3)
        ->and($slugs[0])->toBe('petto');
});
