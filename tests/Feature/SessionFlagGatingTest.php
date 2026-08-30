<?php

use App\Livewire\Athlete\WorkoutSession;
use App\Models\Exercise;
use App\Models\ExerciseSet;
use App\Models\Member;
use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\PersonalRecord;
use App\Models\SessionExercise;
use App\Models\SessionReadinessCheck;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);

    $this->athlete = User::factory()->create()->assignRole('atleta');
    $this->member = Member::factory()->create(['user_id' => $this->athlete->id]);

    $this->trainer = User::factory()->create()->assignRole('trainer');

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

    $exercise = Exercise::factory()->create();

    $this->sessionExercise = SessionExercise::create([
        'session_id' => $this->session->id,
        'exercise_id' => $exercise->id,
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
            'planned_reps' => 10,
            'planned_rir' => 2,
            'planned_weight_kg' => 80,
        ]);
    }
});

// ---------------------------------------------------------------------------
// readiness_check
// ---------------------------------------------------------------------------

it('readiness_check off: submitReadiness aborts 403', function () {
    Setting::write('readiness_check_enabled', false);
    Feature::flushCache();

    Livewire::actingAs($this->athlete)
        ->test(WorkoutSession::class, ['session' => $this->session])
        ->call('submitReadiness', 2, 2, 2, 2, '')
        ->assertForbidden();
});

it('readiness_check off: skipReadiness aborts 403', function () {
    Setting::write('readiness_check_enabled', false);
    Feature::flushCache();

    Livewire::actingAs($this->athlete)
        ->test(WorkoutSession::class, ['session' => $this->session])
        ->call('skipReadiness')
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// exercise_substitution
// ---------------------------------------------------------------------------

it('exercise_substitution off: openSubstitutionModal aborts 403', function () {
    Setting::write('exercise_substitution_enabled', false);
    Feature::flushCache();

    Livewire::actingAs($this->athlete)
        ->test(WorkoutSession::class, ['session' => $this->session])
        ->call('openSubstitutionModal', $this->sessionExercise->id)
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// personal_records
// ---------------------------------------------------------------------------

it('personal_records off: route /athlete/records restituisce 403', function () {
    Setting::write('personal_records_enabled', false);
    Feature::flushCache();

    $this->actingAs($this->athlete)
        ->get(route('athlete.records'))
        ->assertForbidden();
});

it('personal_records off: toast PR assente nel layout atleta', function () {
    Setting::write('personal_records_enabled', false);
    Feature::flushCache();

    $this->actingAs($this->athlete)
        ->get(route('athlete.dashboard'))
        ->assertOk()
        ->assertDontSee('pr-achieved');
});

it('personal_records off: PR viene comunque salvato in DB durante completeSet', function () {
    Setting::write('personal_records_enabled', false);
    Feature::flushCache();
    config(['pr.min_sessions_before_pr' => 0]);

    SessionReadinessCheck::create([
        'training_session_id' => $this->session->id,
        'sleep_quality' => 2,
        'stress_level' => 2,
        'soreness_level' => 2,
        'joint_status' => 2,
    ]);

    $set = ExerciseSet::where('session_exercise_id', $this->sessionExercise->id)
        ->where('set_index', 1)
        ->first();

    Livewire::actingAs($this->athlete)
        ->test(WorkoutSession::class, ['session' => $this->session])
        ->set("setData.{$set->id}.reps", '10')
        ->set("setData.{$set->id}.weight", '100')
        ->set("setData.{$set->id}.rir", '1')
        ->call('completeSet', $set->id);

    expect(PersonalRecord::where('athlete_id', $this->athlete->id)->count())->toBeGreaterThan(0);
});

// ---------------------------------------------------------------------------
// weekly_volume
// ---------------------------------------------------------------------------

it('weekly_volume off: route /athlete/volume restituisce 403', function () {
    Setting::write('weekly_volume_enabled', false);
    Feature::flushCache();

    $this->actingAs($this->athlete)
        ->get(route('athlete.volume'))
        ->assertForbidden();
});

it('weekly_volume off: sidebar Progressi punta a athlete.measurements', function () {
    Setting::write('weekly_volume_enabled', false);
    Feature::flushCache();

    $this->actingAs($this->athlete)
        ->get(route('athlete.dashboard'))
        ->assertOk()
        ->assertSee(route('athlete.measurements'));
});

// ---------------------------------------------------------------------------
// session_recap
// ---------------------------------------------------------------------------

it('session_recap off: route /athlete/session/{id}/recap restituisce 403', function () {
    Setting::write('session_recap_enabled', false);
    Feature::flushCache();

    $this->actingAs($this->athlete)
        ->get(route('athlete.session.recap', $this->session))
        ->assertForbidden();
});

it('session_recap off: link recap assente dalla dashboard', function () {
    Setting::write('session_recap_enabled', false);
    Feature::flushCache();

    $plan = SubscriptionPlan::factory()->create();
    Subscription::factory()->create([
        'member_id' => $this->member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'started_at' => Carbon::today()->subDays(10),
        'expires_at' => Carbon::today()->addDays(20),
    ]);

    $this->session->update(['status' => 'completed', 'completed_at' => now()]);

    $this->actingAs($this->athlete)
        ->get(route('athlete.dashboard'))
        ->assertOk()
        ->assertDontSee(route('athlete.session.recap', $this->session));
});
