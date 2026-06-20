<?php

use App\Models\Exercise;
use App\Models\ExerciseSet;
use App\Models\Member;
use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\Muscle;
use App\Models\SessionExercise;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\WeeklyVolumeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('WeeklyVolumeCalculator esegue <= 5 query con 30 set', function () {
    // Seed minimo
    $muscle = Muscle::factory()->create(['slug' => 'test_muscle', 'muscle_group' => 'chest']);
    $exercise = Exercise::factory()->create(['mechanic' => 'compound']);
    $exercise->muscles()->attach($muscle->id, ['role' => 'primary', 'contribution_pct' => 100]);

    $athlete = User::factory()->create();
    $trainer = User::factory()->create();
    $meso = Mesocycle::factory()->create(['athlete_id' => $athlete->id, 'trainer_id' => $trainer->id]);
    $week = MicrocycleWeek::factory()->create(['mesocycle_id' => $meso->id, 'week_number' => 1]);

    $session = TrainingSession::factory()->create([
        'microcycle_week_id' => $week->id,
        'status' => 'completed',
    ]);
    $se = SessionExercise::factory()->create([
        'session_id' => $session->id,
        'exercise_id' => $exercise->id,
        'planned_sets_count' => 30,
    ]);

    // 30 set working completati
    ExerciseSet::factory()->count(30)->create([
        'session_exercise_id' => $se->id,
        'is_warmup' => false,
        'completed_at' => now(),
    ]);

    $queryCount = 0;
    DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    // Bypassa la cache per il test
    config(['cache.default' => 'array']);
    app(WeeklyVolumeCalculator::class)->calculate($athlete->id, $week->id);

    expect($queryCount)->toBeLessThanOrEqual(5);
});

it('MemberList non genera N+1 su 15 membri con subscription', function () {
    $plan = SubscriptionPlan::create([
        'name' => 'Test Plan',
        'price_cents' => 5000,
        'duration_days' => 30,
    ]);

    User::factory()->count(15)->create()->each(function ($user) use ($plan) {
        $member = Member::factory()->create(['user_id' => $user->id, 'is_active' => true]);
        Subscription::create([
            'member_id' => $member->id,
            'plan_id' => $plan->id,
            'started_at' => now()->subMonth()->toDateString(),
            'expires_at' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ]);
    });

    $queryCount = 0;
    DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    $this->actingAs(User::factory()->create())
        ->get(route('backoffice.members.index'));

    // Con eager loading, aspettiamo <= 6 query (auth, session, members, subscriptions, plans, count)
    expect($queryCount)->toBeLessThanOrEqual(10);
});

it('WorkoutSession carica sessione completa in <= 5 query', function () {
    $athlete = User::factory()->create();
    $trainer = User::factory()->create();
    $meso = Mesocycle::factory()->create(['athlete_id' => $athlete->id, 'trainer_id' => $trainer->id]);
    $week = MicrocycleWeek::factory()->create(['mesocycle_id' => $meso->id, 'week_number' => 1]);
    $session = TrainingSession::factory()->create([
        'microcycle_week_id' => $week->id,
        'status' => 'planned',
    ]);

    $exercise = Exercise::factory()->create();
    $se = SessionExercise::factory()->create([
        'session_id' => $session->id,
        'exercise_id' => $exercise->id,
        'planned_sets_count' => 3,
    ]);
    ExerciseSet::factory()->count(3)->create([
        'session_exercise_id' => $se->id,
        'is_warmup' => false,
    ]);

    $queryCount = 0;
    DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    $this->actingAs($athlete)->get(route('athlete.session', $session->id));

    // mount() fa un load() chain: session, sessionExercises, sets, exercises, week+mesocycle
    expect($queryCount)->toBeLessThanOrEqual(10);
});
