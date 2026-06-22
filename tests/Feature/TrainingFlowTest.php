<?php

use App\Models\Exercise;
use App\Models\Mesocycle;
use App\Models\SessionFeedback;
use App\Models\TemplateSession;
use App\Models\TemplateSessionExercise;
use App\Models\User;
use App\Models\WorkoutTemplate;
use App\Services\MesocycleInstantiationService;
use App\Services\WeeklyProgressionService;
use App\Services\WeeklyVolumeCalculator;
use App\ValueObjects\ProgressionResult;
use Database\Seeders\ExerciseSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('flusso training completo: instantiate → log → volume → progressione', function () {

    // ── Seed prerequisiti ──────────────────────────────────────────────────
    $this->seed([RoleSeeder::class, ExerciseSeeder::class]);

    // ── Utenti ────────────────────────────────────────────────────────────
    $trainer = User::factory()->create(['name' => 'Trainer Test']);
    $trainer->assignRole('trainer');

    $athlete = User::factory()->create(['name' => 'Atleta Test']);
    $athlete->assignRole('atleta');

    // ── Esercizi con almeno un muscolo primary ─────────────────────────────
    $exercises = Exercise::whereHas('muscles', fn ($q) => $q->where('exercise_muscle.role', 'primary'))
        ->take(2)
        ->get();

    expect($exercises)->toHaveCount(2, 'Il seed deve contenere almeno 2 esercizi con muscoli primary.');

    // ── WorkoutTemplate ───────────────────────────────────────────────────
    $template = WorkoutTemplate::create([
        'name' => 'Template Test E2E',
        'description' => 'Test end-to-end flusso training',
        'goal' => 'hypertrophy',
        'periodization_model' => 'linear',
        'weeks_count' => 2,
        'days_per_week' => 2,
        'created_by' => $trainer->id,
        'is_active' => true,
    ]);

    // ── 2 TemplateSession, entrambe settimana 1 ───────────────────────────
    $templateSessions = collect();
    foreach ([['Upper A', 1], ['Lower B', 2]] as [$name, $order]) {
        $ts = TemplateSession::create([
            'template_id' => $template->id,
            'week_number' => 1,
            'name' => $name,
            'order_in_week' => $order,
        ]);
        $templateSessions->push($ts);
    }

    // ── 2 TemplateSessionExercise per ogni sessione ───────────────────────
    foreach ($templateSessions as $ts) {
        foreach ($exercises->values() as $idx => $exercise) {
            TemplateSessionExercise::create([
                'template_session_id' => $ts->id,
                'exercise_id' => $exercise->id,
                'order_in_session' => $idx + 1,
                'technique_type' => 'straight',
                'planned_sets_count' => 3,
                'planned_reps' => 10,
                'planned_rir' => 2,
                'planned_rest_sec' => 90,
            ]);
        }
    }

    // ── STEP 3: MesocycleInstantiationService::instantiate() ──────────────
    /** @var MesocycleInstantiationService $instantiator */
    $instantiator = app(MesocycleInstantiationService::class);

    $mesocycle = $instantiator->instantiate($template, $athlete->id, $trainer->id, [
        'name' => 'Mesociclo Test E2E',
        'goal' => 'hypertrophy',
        'periodization_model' => 'linear',
        'start_date' => today(),
        'weeks_count' => 2,
    ]);

    expect($mesocycle)->toBeInstanceOf(Mesocycle::class);

    // ── STEP 4: verifica struttura del mesociclo ──────────────────────────
    $mesocycle->load('weeks.sessions.sessionExercises.sets');

    $weeks = $mesocycle->weeks->sortBy('week_number')->values();

    // N MicrocycleWeek = weeks_count
    expect($weeks)->toHaveCount(2);

    // Ultima settimana è deload
    expect($weeks->last()->is_deload)->toBeTrue();

    // Settimana 1 ha 2 sessioni (corrispondenti ai 2 TemplateSession)
    $week1 = $weeks->firstWhere('week_number', 1);
    expect($week1->sessions)->toHaveCount(2);

    // Ogni sessione ha 2 SessionExercise, ognuno con planned_sets_count set
    foreach ($week1->sessions as $session) {
        expect($session->sessionExercises)->toHaveCount(2);

        foreach ($session->sessionExercises as $se) {
            expect($se->sets)->not->toBeEmpty();

            foreach ($se->sets as $set) {
                expect($set->planned_reps)->not->toBeNull();
                expect($set->planned_rir)->not->toBeNull();
            }
        }
    }

    // ── STEP 5: completa tutti i set della prima sessione ─────────────────
    $firstSession = $week1->sessions->sortBy('order_in_week')->first();

    foreach ($firstSession->sessionExercises as $se) {
        foreach ($se->sets as $set) {
            $set->update([
                'actual_reps' => 10,
                'actual_weight_kg' => 80.0,
                'actual_rir' => 1,
                'completed_at' => now(),
            ]);
        }
    }

    $firstSession->update(['status' => 'completed']);

    // ── STEP 6: SessionFeedback ───────────────────────────────────────────
    $feedback = SessionFeedback::create([
        'session_id' => $firstSession->id,
        'pump' => 2,
        'soreness_prev' => 1,
        'perceived_effort' => 2,
        'joint_pain' => 0,
        'performance' => 2,
    ]);

    expect($feedback->exists)->toBeTrue();

    // ── STEP 7: WeeklyVolumeCalculator::calculate() ───────────────────────
    /** @var WeeklyVolumeCalculator $volumeCalc */
    $volumeCalc = app(WeeklyVolumeCalculator::class);

    $volume = $volumeCalc->calculate($athlete->id, $week1->id);

    expect($volume)->not->toBeEmpty('Il volume deve includere almeno un muscolo dalla sessione completata.');

    $hasPositiveVolume = collect($volume)->contains(fn ($data) => $data['hard_sets'] > 0);
    expect($hasPositiveVolume)->toBeTrue('Almeno un muscolo deve avere hard_sets > 0.');

    // ── STEP 8: WeeklyProgressionService::progressWeek() ─────────────────
    /** @var WeeklyProgressionService $progressionService */
    $progressionService = app(WeeklyProgressionService::class);

    $result = $progressionService->progressWeek($mesocycle->id, 1);

    expect($result)->toBeInstanceOf(ProgressionResult::class);
    expect($result->action)->toBeIn(['progressed', 'held', 'reduced', 'deload']);
});
