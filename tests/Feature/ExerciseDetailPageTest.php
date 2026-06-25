<?php

use App\Models\Exercise;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed();

    $this->trainer = User::factory()->create();
    $this->trainer->assignRole('trainer');
});

it('risponde 200 per un esercizio esistente tramite slug', function () {
    $exercise = Exercise::first();

    $this->actingAs($this->trainer)
        ->get(route('backoffice.exercises.show', $exercise))
        ->assertStatus(200);
});

it('risponde 404 per uno slug inesistente', function () {
    $this->actingAs($this->trainer)
        ->get('/backoffice/exercises/slug-che-non-esiste-mai')
        ->assertStatus(404);
});

it('la view contiene il nome dell\'esercizio', function () {
    $exercise = Exercise::first();

    $this->actingAs($this->trainer)
        ->get(route('backoffice.exercises.show', $exercise))
        ->assertSee($exercise->name_it);
});

it('la view contiene almeno un muscolo primary', function () {
    $exercise = Exercise::whereHas('muscles', function ($q) {
        $q->where('exercise_muscle.role', 'primary');
    })->first();

    expect($exercise)->not->toBeNull();

    $primaryMuscle = $exercise->muscles()
        ->wherePivot('role', 'primary')
        ->first();

    $this->actingAs($this->trainer)
        ->get(route('backoffice.exercises.show', $exercise))
        ->assertSee($primaryMuscle->name_it)
        ->assertSee('Primario');
});
