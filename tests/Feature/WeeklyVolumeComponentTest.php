<?php

use App\Livewire\Athlete\WeeklyVolume;
use App\Models\Exercise;
use App\Models\ExerciseSet;
use App\Models\Member;
use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\Muscle;
use App\Models\SessionExercise;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

// Crea atleta + membro + mesociclo + settimana e restituisce [$athlete, $week]
function makeAthleteWithWeek(bool $isDeload = false): array
{
    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    $athlete = User::factory()->create();
    $athlete->assignRole('atleta');
    Member::factory()->create(['user_id' => $athlete->id]);

    $mesocycle = Mesocycle::factory()->active()->create([
        'athlete_id' => $athlete->id,
        'trainer_id' => $trainer->id,
    ]);

    $week = MicrocycleWeek::factory()->create([
        'mesocycle_id' => $mesocycle->id,
        'week_number'  => 1,
        'is_deload'    => $isDeload,
        'start_date'   => now()->startOfWeek(),
        'end_date'     => now()->endOfWeek(),
    ]);

    return [$athlete, $week];
}

// Allega muscolo a esercizio con contribution_pct
function attachMuscle(Exercise $exercise, Muscle $muscle, int $pct = 100): void
{
    $exercise->muscles()->attach($muscle->id, ['role' => 'primary', 'contribution_pct' => $pct]);
}

// Crea un set completato per l'atleta nella sessione
function makeCompletedSet(TrainingSession $session, Exercise $exercise, bool $warmup = false): ExerciseSet
{
    $se = SessionExercise::factory()->create([
        'session_id'  => $session->id,
        'exercise_id' => $exercise->id,
    ]);

    return ExerciseSet::factory()->create([
        'session_exercise_id' => $se->id,
        'is_warmup'           => $warmup,
        'completed_at'        => now(),
    ]);
}

it('il componente si monta senza errori per atleta autenticato', function () {
    [$athlete] = makeAthleteWithWeek();

    Livewire::actingAs($athlete)
        ->test(WeeklyVolume::class)
        ->assertStatus(200);
});

it('un trainer non può montare il componente atleta', function () {
    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    $this->actingAs($trainer)
        ->get(route('athlete.volume'))
        ->assertForbidden();
});

it('mostra il volume distribuito su più muscoli via contribution_pct', function () {
    [$athlete, $week] = makeAthleteWithWeek();

    $quad = Muscle::factory()->create(['slug' => 'quadriceps', 'name_it' => 'Quadricipite', 'muscle_group' => 'legs']);
    $glute = Muscle::factory()->create(['slug' => 'gluteus_maximus', 'name_it' => 'Gluteo', 'muscle_group' => 'legs']);

    // Squat: 70% quad, 30% gluteo
    $squat = Exercise::factory()->create();
    attachMuscle($squat, $quad, 70);
    attachMuscle($squat, $glute, 30);

    $session = TrainingSession::factory()->completed()->create(['microcycle_week_id' => $week->id]);

    // 3 set completati = 3 * 0.70 = 2.1 hard set quad, 3 * 0.30 = 0.9 hard set gluteo
    makeCompletedSet($session, $squat);
    makeCompletedSet($session, $squat);
    makeCompletedSet($session, $squat);

    Livewire::actingAs($athlete)
        ->test(WeeklyVolume::class)
        ->assertSet('selectedWeekId', $week->id)
        ->assertSee('Quadricipite')
        ->assertSee('2.1 set');
});

it('i warm-up set sono esclusi dal conteggio', function () {
    [$athlete, $week] = makeAthleteWithWeek();

    $bicep = Muscle::factory()->create(['slug' => 'biceps_brachii', 'name_it' => 'Bicipite', 'muscle_group' => 'arms']);
    $exercise = Exercise::factory()->create();
    attachMuscle($exercise, $bicep, 100);

    $session = TrainingSession::factory()->completed()->create(['microcycle_week_id' => $week->id]);

    // 1 working set + 2 warmup
    makeCompletedSet($session, $exercise, warmup: false);
    makeCompletedSet($session, $exercise, warmup: true);
    makeCompletedSet($session, $exercise, warmup: true);

    $component = Livewire::actingAs($athlete)->test(WeeklyVolume::class);

    // Solo 1.0 hard set (1 working set × 100%), warmup esclusi
    expect($component->get('volumeData')['biceps_brachii']['hard_sets'] ?? null)->toBe(1.0);
});

it('atleta senza landmarks mostra status no_landmark', function () {
    [$athlete, $week] = makeAthleteWithWeek();

    // Muscolo senza entry in volume_landmarks config
    $muscle = Muscle::factory()->create(['slug' => 'brachioradialis', 'name_it' => 'Brachioradiale', 'muscle_group' => 'arms']);
    $exercise = Exercise::factory()->create();
    attachMuscle($exercise, $muscle, 100);

    $session = TrainingSession::factory()->completed()->create(['microcycle_week_id' => $week->id]);
    makeCompletedSet($session, $exercise);

    $component = Livewire::actingAs($athlete)->test(WeeklyVolume::class);

    $vd = $component->get('volumeData');
    expect($vd)->toHaveKey('brachioradialis');
    expect($vd['brachioradialis']['status'])->toBe('no_landmark');
    expect($vd['brachioradialis']['mev'])->toBeNull();
});

it('settimana deload è selezionabile e mostra label deload', function () {
    [$athlete, $week] = makeAthleteWithWeek(isDeload: true);

    $component = Livewire::actingAs($athlete)->test(WeeklyVolume::class);

    $weeks = $component->get('weeks');
    $deloadWeek = collect($weeks)->firstWhere('id', $week->id);

    expect($deloadWeek)->not->toBeNull();
    expect($deloadWeek['is_deload'])->toBeTrue();
    expect($deloadWeek['label'])->toContain('deload');
});

it('nessun mesociclo attivo mostra empty state', function () {
    $athlete = User::factory()->create();
    $athlete->assignRole('atleta');
    Member::factory()->create(['user_id' => $athlete->id]);

    Livewire::actingAs($athlete)
        ->test(WeeklyVolume::class)
        ->assertSee('Nessun mesociclo attivo');
});

it('cambio settimana aggiorna i dati volume', function () {
    [$athlete, $week1] = makeAthleteWithWeek();

    $mesocycle = $week1->mesocycle;

    $week2 = MicrocycleWeek::factory()->create([
        'mesocycle_id' => $mesocycle->id,
        'week_number'  => 2,
        'is_deload'    => false,
        'start_date'   => now()->addWeek()->startOfWeek(),
        'end_date'     => now()->addWeek()->endOfWeek(),
    ]);

    $quad = Muscle::factory()->create(['slug' => 'quadriceps', 'name_it' => 'Quad', 'muscle_group' => 'legs']);
    $exercise = Exercise::factory()->create();
    attachMuscle($exercise, $quad, 100);

    // Volume solo in settimana 2
    $session2 = TrainingSession::factory()->completed()->create(['microcycle_week_id' => $week2->id]);
    makeCompletedSet($session2, $exercise);

    $component = Livewire::actingAs($athlete)->test(WeeklyVolume::class);

    // Settimana 1 (default): quadricipite a 0 hard set (config landmark lo include comunque)
    $vd1 = $component->get('volumeData');
    expect($vd1['quadriceps']['hard_sets'] ?? -1)->toBe(0.0);

    // Cambia a settimana 2: vede 1 set
    $component->set('selectedWeekId', $week2->id);
    expect($component->get('volumeData')['quadriceps']['hard_sets'] ?? 0)->toBe(1.0);
});
