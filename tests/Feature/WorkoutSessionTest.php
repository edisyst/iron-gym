<?php

use App\Livewire\Athlete\SessionFeedbackForm;
use App\Livewire\Athlete\WorkoutSession;
use App\Models\Exercise;
use App\Models\ExerciseSet;
use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\SessionExercise;
use App\Models\SessionFeedback;
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

    // Atleta autenticato
    $this->athlete = User::factory()->create();
    $this->athlete->assignRole('atleta');

    $this->trainer = User::factory()->create();
    $this->trainer->assignRole('trainer');

    // Struttura: Mesocycle → MicrocycleWeek → TrainingSession
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

    // Esercizio con 3 working set
    $exercise = Exercise::factory()->create();

    $this->sessionExercise = SessionExercise::create([
        'session_id' => $this->session->id,
        'exercise_id' => $exercise->id,
        'order_in_session' => 1,
        'technique_type' => 'straight',
        'planned_sets_count' => 3,
        'planned_rest_sec' => 120,
    ]);

    // Crea 3 set non completati
    for ($i = 1; $i <= 3; $i++) {
        ExerciseSet::create([
            'session_exercise_id' => $this->sessionExercise->id,
            'set_index' => $i,
            'is_warmup' => false,
            'planned_reps' => 10,
            'planned_rir' => 2,
        ]);
    }
});

it('il completamento del primo set porta la sessione in in_progress', function () {
    $this->actingAs($this->athlete);

    $set = ExerciseSet::where('session_exercise_id', $this->sessionExercise->id)
        ->where('set_index', 1)
        ->first();

    $component = Livewire::test(WorkoutSession::class, ['session' => $this->session]);

    // La sessione deve essere passata a in_progress al mount
    expect(TrainingSession::find($this->session->id)->status)->toBe('in_progress');

    // Completa il primo set
    $component->set("setData.{$set->id}.reps", '10')
        ->set("setData.{$set->id}.weight", '80')
        ->set("setData.{$set->id}.rir", '2')
        ->call('completeSet', $set->id);

    $updatedSet = ExerciseSet::find($set->id);
    expect($updatedSet->completed_at)->not->toBeNull()
        ->and($updatedSet->actual_reps)->toBe(10)
        ->and((float) $updatedSet->actual_weight_kg)->toBe(80.0)
        ->and($updatedSet->actual_rir)->toBe(2);
});

it('il completamento di tutti i set working abilita il completamento sessione', function () {
    $this->actingAs($this->athlete);

    $sets = ExerciseSet::where('session_exercise_id', $this->sessionExercise->id)->get();

    $component = Livewire::test(WorkoutSession::class, ['session' => $this->session]);

    // canCompleteSession() deve essere false prima di completare tutti i set
    expect($component->instance()->canCompleteSession())->toBeFalse();

    // Completa tutti i set
    foreach ($sets as $set) {
        $component->set("setData.{$set->id}.reps", '10')
            ->set("setData.{$set->id}.weight", '70')
            ->set("setData.{$set->id}.rir", '2')
            ->call('completeSet', $set->id);
    }

    // Ora deve essere possibile completare la sessione
    expect($component->instance()->canCompleteSession())->toBeTrue();
});

it('il feedback post-sessione viene salvato correttamente', function () {
    $this->actingAs($this->athlete);

    // Porta la sessione in stato completed
    $this->session->update([
        'status' => 'completed',
        'started_at' => now()->subHour(),
        'completed_at' => now(),
    ]);

    // Testa il componente SessionFeedbackForm come componente Livewire
    $component = Livewire::test(SessionFeedbackForm::class, [
        'session' => $this->session,
    ]);

    $component
        ->set('pump', 3)
        ->set('sorenessPrev', 1)
        ->set('perceivedEffort', 2)
        ->set('jointPain', 0)
        ->set('performance', 3)
        ->set('sleepHours', 7.5)
        ->set('stressLevel', 1)
        ->set('note', 'Sessione ottima')
        ->call('save');

    $feedback = SessionFeedback::where('session_id', $this->session->id)->first();

    expect($feedback)->not->toBeNull()
        ->and($feedback->pump)->toBe(3)
        ->and($feedback->soreness_prev)->toBe(1)
        ->and($feedback->perceived_effort)->toBe(2)
        ->and($feedback->joint_pain)->toBe(0)
        ->and($feedback->performance)->toBe(3)
        ->and((float) $feedback->sleep_hours)->toBe(7.5)
        ->and($feedback->stress_level)->toBe(1)
        ->and($feedback->note)->toBe('Sessione ottima');
});
