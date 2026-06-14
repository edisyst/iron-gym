<?php

use App\Models\Exercise;
use App\Models\ExerciseSet;
use App\Models\MicrocycleWeek;
use App\Models\SessionExercise;
use App\Models\TemplateSession;
use App\Models\TemplateSessionExercise;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\WorkoutTemplate;
use App\Services\MesocycleInstantiationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Crea i ruoli necessari (RefreshDatabase non esegue i seeder)
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);

    // Trainer autenticato
    $this->trainer = User::factory()->create();
    $this->trainer->assignRole('trainer');

    // Atleta
    $this->athlete = User::factory()->create();
    $this->athlete->assignRole('atleta');

    // Esercizio esistente nel catalogo
    $this->exercise = Exercise::factory()->create();

    // Template base: 2 settimane, 2 sessioni in settimana 1
    $this->template = WorkoutTemplate::factory()->create([
        'created_by' => $this->trainer->id,
        'weeks_count' => 4,
        'periodization_model' => 'linear',
        'goal' => 'hypertrophy',
    ]);

    // Sessione 1 settimana 1
    $ts1 = TemplateSession::create([
        'template_id' => $this->template->id,
        'week_number' => 1,
        'name' => 'Push A',
        'order_in_week' => 1,
    ]);

    // Sessione 2 settimana 1
    $ts2 = TemplateSession::create([
        'template_id' => $this->template->id,
        'week_number' => 1,
        'name' => 'Pull A',
        'order_in_week' => 2,
    ]);

    // Due esercizi nella sessione 1 (3 set ciascuno, planned_reps=10, planned_rir=2)
    TemplateSessionExercise::create([
        'template_session_id' => $ts1->id,
        'exercise_id' => $this->exercise->id,
        'order_in_session' => 1,
        'technique_type' => 'straight',
        'planned_sets_count' => 3,
        'planned_reps' => 10,
        'planned_rir' => 2,
        'planned_rest_sec' => 120,
    ]);

    TemplateSessionExercise::create([
        'template_session_id' => $ts1->id,
        'exercise_id' => $this->exercise->id,
        'order_in_session' => 2,
        'technique_type' => 'straight',
        'planned_sets_count' => 4,
        'planned_reps' => 8,
        'planned_rir' => 1,
        'planned_rest_sec' => 180,
    ]);

    // Un esercizio nella sessione 2
    TemplateSessionExercise::create([
        'template_session_id' => $ts2->id,
        'exercise_id' => $this->exercise->id,
        'order_in_session' => 1,
        'technique_type' => 'straight',
        'planned_sets_count' => 3,
        'planned_reps' => 12,
        'planned_rir' => 3,
        'planned_rest_sec' => 90,
    ]);

    $this->service = app(MesocycleInstantiationService::class);

    $this->params = [
        'name' => 'Test Meso',
        'goal' => 'hypertrophy',
        'periodization_model' => 'linear',
        'start_date' => '2026-01-06', // lunedì
        'weeks_count' => 4,
    ];
});

it("l'istanziamento di un template crea il numero corretto di settimane", function () {
    $mesocycle = $this->service->instantiate(
        $this->template,
        $this->athlete->id,
        $this->trainer->id,
        $this->params
    );

    $weekCount = MicrocycleWeek::where('mesocycle_id', $mesocycle->id)->count();

    expect($weekCount)->toBe(4);
});

it("l'istanziamento segna l'ultima settimana come deload", function () {
    $mesocycle = $this->service->instantiate(
        $this->template,
        $this->athlete->id,
        $this->trainer->id,
        $this->params
    );

    $lastWeek = MicrocycleWeek::where('mesocycle_id', $mesocycle->id)
        ->orderByDesc('week_number')
        ->first();

    expect($lastWeek->is_deload)->toBeTrue()
        ->and($lastWeek->week_number)->toBe(4);

    // Le prime settimane non sono deload
    $firstWeek = MicrocycleWeek::where('mesocycle_id', $mesocycle->id)
        ->where('week_number', 1)
        ->first();

    expect($firstWeek->is_deload)->toBeFalse();
});

it("l'istanziamento crea le sessioni nelle settimane giuste", function () {
    $mesocycle = $this->service->instantiate(
        $this->template,
        $this->athlete->id,
        $this->trainer->id,
        $this->params
    );

    // Il template ha 2 sessioni in settimana 1: devono essere nella MicrocycleWeek week_number=1
    $week1 = MicrocycleWeek::where('mesocycle_id', $mesocycle->id)
        ->where('week_number', 1)
        ->first();

    expect($week1)->not->toBeNull();

    $sessionCount = TrainingSession::where('microcycle_week_id', $week1->id)->count();
    expect($sessionCount)->toBe(2);

    // Nessuna sessione in settimana 2 (il template non ne ha)
    $week2 = MicrocycleWeek::where('mesocycle_id', $mesocycle->id)
        ->where('week_number', 2)
        ->first();

    $sessionCount2 = TrainingSession::where('microcycle_week_id', $week2->id)->count();
    expect($sessionCount2)->toBe(0);
});

it("l'istanziamento crea le sessioni con la scheduled_date corretta", function () {
    $mesocycle = $this->service->instantiate(
        $this->template,
        $this->athlete->id,
        $this->trainer->id,
        $this->params // start_date = 2026-01-06 (lunedì)
    );

    $week1 = MicrocycleWeek::where('mesocycle_id', $mesocycle->id)
        ->where('week_number', 1)
        ->first();

    // Sessione order_in_week=1 → 2026-01-06 (lunedì), order_in_week=2 → 2026-01-07 (martedì)
    $session1 = TrainingSession::where('microcycle_week_id', $week1->id)
        ->where('order_in_week', 1)
        ->first();

    $session2 = TrainingSession::where('microcycle_week_id', $week1->id)
        ->where('order_in_week', 2)
        ->first();

    expect($session1->scheduled_date->format('Y-m-d'))->toBe('2026-01-06')
        ->and($session2->scheduled_date->format('Y-m-d'))->toBe('2026-01-07');
});

it("l'istanziamento crea i set con i parametri planned corretti", function () {
    $mesocycle = $this->service->instantiate(
        $this->template,
        $this->athlete->id,
        $this->trainer->id,
        $this->params
    );

    // Sessione "Push A" in settimana 1
    $week1 = MicrocycleWeek::where('mesocycle_id', $mesocycle->id)
        ->where('week_number', 1)
        ->first();

    $pushA = TrainingSession::where('microcycle_week_id', $week1->id)
        ->where('name', 'Push A')
        ->first();

    // Primo esercizio: 3 set, 10 reps, RIR 2
    $firstExercise = SessionExercise::where('session_id', $pushA->id)
        ->where('order_in_session', 1)
        ->first();

    $sets = ExerciseSet::where('session_exercise_id', $firstExercise->id)
        ->orderBy('set_index')
        ->get();

    expect($sets)->toHaveCount(3);

    foreach ($sets as $index => $set) {
        expect($set->set_index)->toBe($index + 1)
            ->and($set->planned_reps)->toBe(10)
            ->and($set->planned_rir)->toBe(2)
            ->and($set->planned_weight_kg)->toBeNull()
            ->and($set->actual_reps)->toBeNull()
            ->and((bool) $set->is_warmup)->toBeFalse();
    }

    // Secondo esercizio: 4 set
    $secondExercise = SessionExercise::where('session_id', $pushA->id)
        ->where('order_in_session', 2)
        ->first();

    $sets2 = ExerciseSet::where('session_exercise_id', $secondExercise->id)->get();
    expect($sets2)->toHaveCount(4);
});
