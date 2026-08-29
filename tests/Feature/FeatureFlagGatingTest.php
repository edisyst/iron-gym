<?php

use App\Livewire\Athlete\Booking;
use App\Livewire\Athlete\Dashboard;
use App\Livewire\Athlete\WorkoutSession;
use App\Models\Member;
use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\Setting;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestore', 'guard_name' => 'web']);

    $this->athlete = User::factory()->create()->assignRole('atleta');
    $this->member = Member::factory()->create(['user_id' => $this->athlete->id]);
});

// --- messaging ---

it('messaging off: route /athlete/messages restituisce 403', function () {
    Setting::write('messaging_enabled', false);
    Feature::flushCache();

    $this->actingAs($this->athlete)
        ->get(route('athlete.messages'))
        ->assertForbidden();
});

it('messaging on: route /athlete/messages accessibile', function () {
    Setting::write('messaging_enabled', true);
    Feature::flushCache();

    $this->actingAs($this->athlete)
        ->get(route('athlete.messages'))
        ->assertOk();
});

// --- pt_bookings ---

it('pt_bookings off: bookPt restituisce 403', function () {
    Setting::write('pt_bookings_enabled', false);
    Feature::flushCache();

    $trainer = User::factory()->create()->assignRole('trainer');
    $mesocycle = Mesocycle::factory()->create(['athlete_id' => $this->athlete->id, 'trainer_id' => $trainer->id]);
    $week = MicrocycleWeek::factory()->create(['mesocycle_id' => $mesocycle->id]);
    TrainingSession::factory()->create(['microcycle_week_id' => $week->id, 'status' => 'in_progress']);

    Livewire::actingAs($this->athlete)
        ->test(Booking::class)
        ->call('bookPt', $trainer->id, now()->addDay()->toDateString(), '10:00')
        ->assertForbidden();
});

it('pt_bookings off: dashboard atleta non carica pt bookings', function () {
    Setting::write('pt_bookings_enabled', false);
    Feature::flushCache();

    $trainer = User::factory()->create()->assignRole('trainer');
    $mesocycle = Mesocycle::factory()->create(['athlete_id' => $this->athlete->id, 'trainer_id' => $trainer->id]);
    MicrocycleWeek::factory()->create(['mesocycle_id' => $mesocycle->id]);

    $component = Livewire::actingAs($this->athlete)->test(Dashboard::class);

    expect($component->get('upcomingPtBookings'))->toBeEmpty();
});

// --- session_recap ---

it('session_recap off: route /athlete/session/{id}/recap restituisce 403', function () {
    Setting::write('session_recap_enabled', false);
    Feature::flushCache();

    $trainer = User::factory()->create()->assignRole('trainer');
    $mesocycle = Mesocycle::factory()->create(['athlete_id' => $this->athlete->id, 'trainer_id' => $trainer->id]);
    $week = MicrocycleWeek::factory()->create(['mesocycle_id' => $mesocycle->id]);
    $session = TrainingSession::factory()->create(['microcycle_week_id' => $week->id, 'status' => 'completed']);

    $this->actingAs($this->athlete)
        ->get(route('athlete.session.recap', $session))
        ->assertForbidden();
});

// --- personal_records ---

it('personal_records off: route /athlete/records restituisce 403', function () {
    Setting::write('personal_records_enabled', false);
    Feature::flushCache();

    $this->actingAs($this->athlete)
        ->get(route('athlete.records'))
        ->assertForbidden();
});

// --- weekly_volume ---

it('weekly_volume off: route /athlete/volume restituisce 403', function () {
    Setting::write('weekly_volume_enabled', false);
    Feature::flushCache();

    $this->actingAs($this->athlete)
        ->get(route('athlete.volume'))
        ->assertForbidden();
});

// --- readiness_check ---

it('readiness_check off: mount avvia sessione senza mostrare modal', function () {
    Setting::write('readiness_check_enabled', false);
    Feature::flushCache();

    $trainer = User::factory()->create()->assignRole('trainer');
    $mesocycle = Mesocycle::factory()->create(['athlete_id' => $this->athlete->id, 'trainer_id' => $trainer->id]);
    $week = MicrocycleWeek::factory()->create(['mesocycle_id' => $mesocycle->id]);
    $session = TrainingSession::factory()->create(['microcycle_week_id' => $week->id, 'status' => 'planned']);

    Livewire::actingAs($this->athlete)
        ->test(WorkoutSession::class, ['session' => $session])
        ->assertSet('showReadinessModal', false);
});

it('readiness_check on: mount mostra modal se nessun check presente', function () {
    Setting::write('readiness_check_enabled', true);
    Feature::flushCache();

    $trainer = User::factory()->create()->assignRole('trainer');
    $mesocycle = Mesocycle::factory()->create(['athlete_id' => $this->athlete->id, 'trainer_id' => $trainer->id]);
    $week = MicrocycleWeek::factory()->create(['mesocycle_id' => $mesocycle->id]);
    $session = TrainingSession::factory()->create(['microcycle_week_id' => $week->id, 'status' => 'planned']);

    Livewire::actingAs($this->athlete)
        ->test(WorkoutSession::class, ['session' => $session])
        ->assertSet('showReadinessModal', true);
});
