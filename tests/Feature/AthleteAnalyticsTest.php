<?php

use App\Livewire\Backoffice\Athletes\AthleteAnalytics;
use App\Models\Mesocycle;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
    $this->athlete = User::factory()->create()->assignRole('atleta');

    Mesocycle::factory()->create([
        'athlete_id' => $this->athlete->id,
        'trainer_id' => $this->trainer->id,
    ]);
});

it('gestore può accedere alle analytics di qualsiasi atleta', function () {
    Livewire::actingAs($this->gestore)
        ->test(AthleteAnalytics::class, ['athleteId' => $this->athlete->id])
        ->assertOk();
});

it('trainer con mesociclo assegnato può accedere alle analytics', function () {
    Livewire::actingAs($this->trainer)
        ->test(AthleteAnalytics::class, ['athleteId' => $this->athlete->id])
        ->assertOk();
});

it('trainer senza mesociclo assegnato viene respinto con 403', function () {
    Livewire::actingAs($this->otherTrainer)
        ->test(AthleteAnalytics::class, ['athleteId' => $this->athlete->id])
        ->assertStatus(403);
});

it('atleta inesistente lancia eccezione', function () {
    expect(fn () => Livewire::actingAs($this->gestore)
        ->test(AthleteAnalytics::class, ['athleteId' => 99999])
    )->toThrow(ModelNotFoundException::class);
});
