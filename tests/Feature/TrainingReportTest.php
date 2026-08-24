<?php

use App\Livewire\Backoffice\Reports\TrainingReport;
use App\Models\Member;
use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'gestore', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);

    $this->gestore = User::factory()->create()->assignRole('gestore');
    $this->trainer = User::factory()->create()->assignRole('trainer');
    $this->otherTrainer = User::factory()->create()->assignRole('trainer');

    $athlete1 = User::factory()->create()->assignRole('atleta');
    $athlete2 = User::factory()->create()->assignRole('atleta');
    Member::factory()->create(['user_id' => $athlete1->id]);
    Member::factory()->create(['user_id' => $athlete2->id]);

    $this->meso1 = Mesocycle::factory()->create([
        'athlete_id' => $athlete1->id,
        'trainer_id' => $this->trainer->id,
        'status' => 'active',
        'weeks_count' => 2,
    ]);
    $this->meso2 = Mesocycle::factory()->create([
        'athlete_id' => $athlete2->id,
        'trainer_id' => $this->otherTrainer->id,
        'status' => 'active',
        'weeks_count' => 2,
    ]);

    $week1 = MicrocycleWeek::factory()->create(['mesocycle_id' => $this->meso1->id, 'week_number' => 1]);
    $week2 = MicrocycleWeek::factory()->create(['mesocycle_id' => $this->meso2->id, 'week_number' => 1]);

    TrainingSession::factory()->create([
        'microcycle_week_id' => $week1->id,
        'status' => 'completed',
        'completed_at' => now(),
        'scheduled_date' => now()->toDateString(),
    ]);
    TrainingSession::factory()->create([
        'microcycle_week_id' => $week2->id,
        'status' => 'completed',
        'completed_at' => now(),
        'scheduled_date' => now()->toDateString(),
    ]);
});

it('gestore vede tutti gli atleti nel report', function () {
    $component = Livewire::actingAs($this->gestore)
        ->test(TrainingReport::class)
        ->assertOk();

    expect($component->viewData('athleteRows'))->toHaveCount(2);
});

it('trainer vede solo i propri atleti', function () {
    $component = Livewire::actingAs($this->trainer)
        ->test(TrainingReport::class)
        ->assertOk();

    $rows = $component->viewData('athleteRows');
    expect($rows)->toHaveCount(1)
        ->and($rows->first()->mesociclo)->toBe($this->meso1->name);
});

it('trainer non vede atleti di altri trainer', function () {
    $component = Livewire::actingAs($this->trainer)
        ->test(TrainingReport::class);

    $rows = $component->viewData('athleteRows');
    $mesoNames = $rows->pluck('mesociclo')->toArray();
    expect($mesoNames)->not->toContain($this->meso2->name);
});

it('openDrilldown gestore può aprire qualsiasi atleta', function () {
    $athlete = User::role('atleta')->first();

    Livewire::actingAs($this->gestore)
        ->test(TrainingReport::class)
        ->call('openDrilldown', $athlete->id)
        ->assertSet('drilldownAthleteId', $athlete->id);
});

it('openDrilldown trainer con atleta assegnato funziona', function () {
    $athleteId = $this->meso1->athlete_id;

    Livewire::actingAs($this->trainer)
        ->test(TrainingReport::class)
        ->call('openDrilldown', $athleteId)
        ->assertSet('drilldownAthleteId', $athleteId);
});

it('openDrilldown trainer senza atleta assegnato viene respinto con 403', function () {
    $otherAthleteId = $this->meso2->athlete_id;

    Livewire::actingAs($this->trainer)
        ->test(TrainingReport::class)
        ->call('openDrilldown', $otherAthleteId)
        ->assertStatus(403);
});
