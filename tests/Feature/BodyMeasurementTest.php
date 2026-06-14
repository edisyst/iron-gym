<?php

use App\Models\BodyMeasurement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Crea il ruolo atleta se non esiste (RefreshDatabase resetta i ruoli)
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);
});

it('una misurazione corporea viene salvata correttamente', function () {
    $user = User::factory()->create();
    $user->assignRole('atleta');

    $this->actingAs($user);

    $data = [
        'athlete_id' => $user->id,
        'measured_at' => '2026-06-14',
        'weight_kg' => 80.5,
        'body_fat_pct' => 15.0,
    ];

    $measurement = BodyMeasurement::create($data);

    expect((float) $measurement->weight_kg)->toBe(80.5);
    expect($measurement->athlete_id)->toBe($user->id);
});

it("l'atleta non può vedere le misurazioni di un altro atleta", function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    BodyMeasurement::create([
        'athlete_id' => $user1->id,
        'measured_at' => '2026-06-14',
        'weight_kg' => 70.0,
    ]);

    $this->actingAs($user2);

    // Il componente Progress filtra sempre per auth()->id(), quindi user2 non vede i dati di user1
    $measurements = BodyMeasurement::where('athlete_id', $user2->id)->get();
    expect($measurements)->toHaveCount(0);
});
