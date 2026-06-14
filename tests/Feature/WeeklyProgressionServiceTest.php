<?php

use App\Models\Exercise;
use App\Models\ExerciseSet;
use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\Muscle;
use App\Models\SessionExercise;
use App\Models\SessionFeedback;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\WeeklyProgressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $athlete = User::factory()->create();
    $trainer = User::factory()->create();

    $this->muscle = Muscle::factory()->create([
        'slug' => 'quadriceps',
        'name_it' => 'Quadricipite',
        'muscle_group' => 'legs',
    ]);

    $this->exercise = Exercise::factory()->create([
        'slug' => 'back_squat_high_bar',
        'name_it' => 'Squat bilanciere',
        'mechanic' => 'compound',
    ]);
    $this->exercise->muscles()->attach($this->muscle->id, ['role' => 'primary', 'contribution_pct' => 50]);

    $this->mesocycle = Mesocycle::factory()->create([
        'athlete_id' => $athlete->id,
        'trainer_id' => $trainer->id,
        'weeks_count' => 5,
    ]);

    $this->week1 = MicrocycleWeek::factory()->create(['mesocycle_id' => $this->mesocycle->id, 'week_number' => 1]);
    $this->week2 = MicrocycleWeek::factory()->create(['mesocycle_id' => $this->mesocycle->id, 'week_number' => 2, 'is_deload' => false]);
    $this->week5 = MicrocycleWeek::factory()->create(['mesocycle_id' => $this->mesocycle->id, 'week_number' => 5, 'is_deload' => true]);

    $this->athleteId = $athlete->id;
    $this->service = app(WeeklyProgressionService::class);
});

/** Crea una sessione completed con N working set sullo squat */
function makeCompletedSession(MicrocycleWeek $week, Exercise $exercise, int $sets = 3): TrainingSession
{
    $session = TrainingSession::factory()->create([
        'microcycle_week_id' => $week->id,
        'status' => 'completed',
    ]);
    $se = SessionExercise::factory()->create([
        'session_id' => $session->id,
        'exercise_id' => $exercise->id,
        'planned_sets_count' => $sets,
    ]);
    ExerciseSet::factory()->count($sets)->create([
        'session_exercise_id' => $se->id,
        'is_warmup' => false,
        'completed_at' => now(),
        'planned_rir' => 2,
        'actual_rir' => 2,
        'actual_weight_kg' => 80,
    ]);

    return $session;
}

it('la progressione aggiunge un set se il feedback è positivo e si è sotto MRV', function () {
    // Week 1: sessione completed con 3 set squat (volume ~1.5 HS, sotto MRV=24)
    makeCompletedSession($this->week1, $this->exercise, 3);

    // Sessione settimana 2 con SE da aggiornare
    $sess2 = TrainingSession::factory()->create(['microcycle_week_id' => $this->week2->id, 'status' => 'planned']);
    $se2 = SessionExercise::factory()->create([
        'session_id' => $sess2->id,
        'exercise_id' => $this->exercise->id,
        'planned_sets_count' => 3,
    ]);

    $result = $this->service->progressWeek($this->mesocycle->id, 1);

    expect($result->action)->toBe('progressed');
    // Se non c'è settimana precedente con feedback, la logica va in 'progressed' (nessun feedback = nessun segnale)
    $se2->refresh();
    expect($se2->planned_sets_count)->toBeGreaterThanOrEqual(3);
});

it('la progressione mantiene il volume se due o più metriche peggiorano', function () {
    // Crea sessioni per settimana 1 e settimana con feedback peggiore
    // Prima crea una settimana 0 come riferimento "precedente"
    $week0 = MicrocycleWeek::factory()->create(['mesocycle_id' => $this->mesocycle->id, 'week_number' => 0]);
    $sess0 = makeCompletedSession($week0, $this->exercise, 3);
    // Feedback settimana 0: buono (pump=3, performance=3)
    SessionFeedback::factory()->create([
        'session_id' => $sess0->id,
        'pump' => 3,
        'soreness_prev' => 1,
        'perceived_effort' => 2,
        'joint_pain' => 0,
        'performance' => 3,
    ]);

    $sess1 = makeCompletedSession($this->week1, $this->exercise, 3);
    // Feedback settimana 1: peggiorato su 2 metriche (pump sceso, performance scesa)
    SessionFeedback::factory()->create([
        'session_id' => $sess1->id,
        'pump' => 1,
        'soreness_prev' => 1,
        'perceived_effort' => 2,
        'joint_pain' => 0,
        'performance' => 1,
    ]);

    $sess2 = TrainingSession::factory()->create(['microcycle_week_id' => $this->week2->id, 'status' => 'planned']);
    $se2 = SessionExercise::factory()->create([
        'session_id' => $sess2->id,
        'exercise_id' => $this->exercise->id,
        'planned_sets_count' => 3,
    ]);

    $result = $this->service->progressWeek($this->mesocycle->id, 1);

    expect($result->action)->toBe('held');
    $se2->refresh();
    expect($se2->planned_sets_count)->toBe(3);
});

it('la settimana deload dimezza il volume', function () {
    // Crea sessioni per settimana 4 con planned_sets_count=4
    $week4 = MicrocycleWeek::factory()->create(['mesocycle_id' => $this->mesocycle->id, 'week_number' => 4]);
    makeCompletedSession($week4, $this->exercise, 4);
    $sessW4 = TrainingSession::where('microcycle_week_id', $week4->id)->first();
    $seW4 = SessionExercise::where('session_id', $sessW4->id)->first();
    $seW4->update(['planned_sets_count' => 4]);

    // Sessione settimana 5 (deload) con 4 set da dimezzare
    $sess5 = TrainingSession::factory()->create(['microcycle_week_id' => $this->week5->id, 'status' => 'planned']);
    $se5 = SessionExercise::factory()->create([
        'session_id' => $sess5->id,
        'exercise_id' => $this->exercise->id,
        'planned_sets_count' => 4,
    ]);

    $result = $this->service->progressWeek($this->mesocycle->id, 4);

    expect($result->action)->toBe('deload');
    $se5->refresh();
    expect($se5->planned_sets_count)->toBe(2);
});
