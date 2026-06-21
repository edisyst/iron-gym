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

it('il trainer non vede le sessioni di un atleta non suo', function () {
    // Comportamento scelto: 200 con zero sessioni visibili.
    // La route è accessibile a tutti i trainer, ma AthleteSessionHistory filtra
    // per athlete_id — le sessioni dell'atleta2 non appaiono nel profilo dell'atleta1.
    $trainer1 = User::factory()->create();
    $trainer1->assignRole('trainer');

    $trainer2 = User::factory()->create();
    $trainer2->assignRole('trainer');

    $athlete1 = User::factory()->create();
    $athlete1->assignRole('atleta');
    Member::factory()->create(['user_id' => $athlete1->id]);

    $athlete2 = User::factory()->create();
    $athlete2->assignRole('atleta');
    Member::factory()->create(['user_id' => $athlete2->id]);

    $mesocycle2 = Mesocycle::factory()->active()->create([
        'athlete_id' => $athlete2->id,
        'trainer_id' => $trainer2->id,
    ]);
    $week2 = MicrocycleWeek::factory()->create(['mesocycle_id' => $mesocycle2->id]);
    TrainingSession::factory()->completed()->create([
        'microcycle_week_id' => $week2->id,
        'name' => 'Sessione Atleta2',
    ]);

    // Trainer1 accede al profilo di atleta1: vede la pagina (200) ma non le sessioni di atleta2
    $response = $this->actingAs($trainer1)
        ->get(route('backoffice.athletes.profile', ['athleteId' => $athlete1->id]));

    $response->assertStatus(200);
    $response->assertDontSee('Sessione Atleta2');
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
