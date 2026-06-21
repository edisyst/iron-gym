<?php

use App\Models\Exercise;
use App\Models\MovementPattern;
use App\Models\User;
use Spatie\Permission\Models\Role;

/*
 * Smoke test — progettato per girare su staging con dati reali.
 * Non usa RefreshDatabase: sola lettura.
 *
 * Esecuzione locale standard (SQLite in-memory): i test vengono saltati.
 * Esecuzione su staging:
 *   php vendor/bin/pest tests/Feature/SmokeTest.php --no-coverage
 */

uses()->group('smoke');

beforeEach(function (): void {
    if (config('database.default') === 'sqlite' && config('database.connections.sqlite.database') === ':memory:') {
        test()->markTestSkipped('Smoke test richiede DB MySQL staging reale.');
    }
});

it('la homepage del backoffice risponde 200 per utente autenticato', function (): void {
    $user = User::role('gestore')->first()
        ?? User::role('trainer')->first()
        ?? User::first();

    expect($user)->not->toBeNull('Nessun utente in DB. Eseguire pilot:init prima.');

    $this->actingAs($user)
        ->get('/backoffice/dashboard')
        ->assertOk();
});

it('la dashboard atleta risponde 200 per atleta autenticato', function (): void {
    $user = User::role('atleta')->first();

    expect($user)->not->toBeNull('Nessun atleta in DB.');

    $this->actingAs($user)
        ->get('/athlete/dashboard')
        ->assertOk();
});

it("l'endpoint health risponde 200", function (): void {
    $this->get('/health')->assertOk();
});

it('il seed esercizi ha caricato 83 esercizi', function (): void {
    $count = Exercise::count();

    expect($count)->toBe(83, "Trovati {$count} esercizi invece di 83. Eseguire ExerciseSeeder.");
});

it('i 27 movement pattern sono presenti', function (): void {
    $count = MovementPattern::count();

    expect($count)->toBe(27, "Trovati {$count} movement pattern invece di 27.");
});

it('i quattro ruoli spatie esistono', function (): void {
    $roles = Role::pluck('name')->toArray();

    expect($roles)->toContain('gestore')
        ->toContain('trainer')
        ->toContain('receptionist')
        ->toContain('atleta');
});
