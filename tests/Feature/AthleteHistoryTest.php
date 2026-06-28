<?php

use App\Models\Member;
use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('il trainer vede lo storico sessioni di un suo atleta', function () {
    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    $athlete = User::factory()->create();
    $athlete->assignRole('atleta');
    Member::factory()->create(['user_id' => $athlete->id]);

    $mesocycle = Mesocycle::factory()->active()->create([
        'athlete_id' => $athlete->id,
        'trainer_id' => $trainer->id,
    ]);

    $week = MicrocycleWeek::factory()->create(['mesocycle_id' => $mesocycle->id]);

    $session = TrainingSession::factory()->completed()->create([
        'microcycle_week_id' => $week->id,
        'name' => 'Push A Test',
    ]);

    $response = $this->actingAs($trainer)
        ->get(route('backoffice.athletes.profile', ['athleteId' => $athlete->id]));

    $response->assertStatus(200);
    $response->assertSee('Push A Test');
});

it('il trainer ottiene 403 sul profilo di un atleta non suo', function () {
    $trainer1 = User::factory()->create();
    $trainer1->assignRole('trainer');

    $trainer2 = User::factory()->create();
    $trainer2->assignRole('trainer');

    $athlete1 = User::factory()->create();
    $athlete1->assignRole('atleta');
    Member::factory()->create(['user_id' => $athlete1->id]);

    // Nessun mesociclo tra trainer1 e athlete1: accesso negato
    $this->actingAs($trainer1)
        ->get(route('backoffice.athletes.profile', ['athleteId' => $athlete1->id]))
        ->assertStatus(403);
});

it('l atleta non può accedere al profilo backoffice', function () {
    $athlete = User::factory()->create();
    $athlete->assignRole('atleta');
    Member::factory()->create(['user_id' => $athlete->id]);

    $response = $this->actingAs($athlete)
        ->get(route('backoffice.athletes.profile', ['athleteId' => $athlete->id]));

    $response->assertStatus(403);
});

it('il gestore vede lo storico di qualsiasi atleta', function () {
    $gestore = User::factory()->create();
    $gestore->assignRole('gestore');

    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    $athlete = User::factory()->create();
    $athlete->assignRole('atleta');
    Member::factory()->create(['user_id' => $athlete->id]);

    $mesocycle = Mesocycle::factory()->active()->create([
        'athlete_id' => $athlete->id,
        'trainer_id' => $trainer->id,
    ]);
    $week = MicrocycleWeek::factory()->create(['mesocycle_id' => $mesocycle->id]);
    TrainingSession::factory()->completed()->create([
        'microcycle_week_id' => $week->id,
        'name' => 'Legs A Gestore',
    ]);

    $response = $this->actingAs($gestore)
        ->get(route('backoffice.athletes.profile', ['athleteId' => $athlete->id]));

    $response->assertStatus(200);
    $response->assertSee('Legs A Gestore');
});
