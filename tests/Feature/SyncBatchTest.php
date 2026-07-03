<?php

use App\Models\Exercise;
use App\Models\ExerciseSet;
use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\SessionExercise;
use App\Models\SyncOperation;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makeAthleteWithSet(bool $planned = true): array
{
    $athlete = User::factory()->create();
    $athlete->assignRole('atleta');

    $mesocycle = Mesocycle::factory()->active()->create(['athlete_id' => $athlete->id]);
    $week = MicrocycleWeek::factory()->create(['mesocycle_id' => $mesocycle->id]);
    $session = TrainingSession::factory()->create(['microcycle_week_id' => $week->id]);
    $se = SessionExercise::factory()->create([
        'session_id' => $session->id,
        'exercise_id' => Exercise::factory()->create()->id,
    ]);

    $set = $planned
        ? ExerciseSet::factory()->planned()->create([
            'session_exercise_id' => $se->id,
            'planned_reps' => 10,
            'planned_weight_kg' => 80.0,
            'planned_rir' => 2,
        ])
        : ExerciseSet::factory()->create([
            'session_exercise_id' => $se->id,
            'planned_reps' => 10,
            'planned_weight_kg' => 80.0,
        ]);

    return compact('athlete', 'set', 'se', 'session');
}

it('batch valido quick_log aggiorna il set e registra uuid', function () {
    ['athlete' => $athlete, 'set' => $set] = makeAthleteWithSet();

    $uuid = (string) Str::uuid();

    $this->actingAs($athlete)
        ->postJson(route('athlete.session.sync'), [
            'operations' => [[
                'client_uuid' => $uuid,
                'operation' => 'quick_log',
                'client_timestamp' => now()->subSeconds(10)->getTimestampMs(),
                'payload' => ['set_id' => $set->id],
            ]],
        ])
        ->assertOk()
        ->assertJsonPath('results.0.status', 'ok');

    expect($set->fresh()->completed_at)->not->toBeNull();
    expect($set->fresh()->actual_reps)->toBe(10);
    expect(SyncOperation::where('client_uuid', $uuid)->exists())->toBeTrue();
});

it('replay dello stesso client_uuid viene ignorato senza doppia scrittura', function () {
    ['athlete' => $athlete, 'set' => $set] = makeAthleteWithSet();

    $uuid = (string) Str::uuid();
    $payload = [
        'operations' => [[
            'client_uuid' => $uuid,
            'operation' => 'quick_log',
            'client_timestamp' => now()->subSeconds(10)->getTimestampMs(),
            'payload' => ['set_id' => $set->id],
        ]],
    ];

    $this->actingAs($athlete)->postJson(route('athlete.session.sync'), $payload)->assertOk();

    // Modifica manuale per rilevare una seconda scrittura
    $set->update(['actual_reps' => 99]);

    $this->actingAs($athlete)
        ->postJson(route('athlete.session.sync'), $payload)
        ->assertOk()
        ->assertJsonPath('results.0.status', 'skipped');

    // Il valore 99 rimane: nessuna sovrascrittura
    expect($set->fresh()->actual_reps)->toBe(99);
});

it('conflitto last-write-wins: server più recente del client, set non sovrascritto', function () {
    ['athlete' => $athlete, 'set' => $set] = makeAthleteWithSet(planned: false);

    // Il set ha completed_at = adesso (simulazione scrittura server recente)
    $serverTime = now();
    $set->update(['completed_at' => $serverTime, 'actual_reps' => 12]);

    // Il client manda un timestamp più vecchio del completed_at del server
    $oldClientTimestamp = $serverTime->clone()->subSeconds(30)->getTimestampMs();

    $this->actingAs($athlete)
        ->postJson(route('athlete.session.sync'), [
            'operations' => [[
                'client_uuid' => (string) Str::uuid(),
                'operation' => 'complete_set',
                'client_timestamp' => $oldClientTimestamp,
                'payload' => ['set_id' => $set->id, 'reps' => 5, 'weight' => 60.0],
            ]],
        ])
        ->assertOk()
        ->assertJsonPath('results.0.status', 'skipped_conflict');

    // Il server mantiene actual_reps = 12
    expect($set->fresh()->actual_reps)->toBe(12);
});

it('atleta non può scrivere su set di un altro atleta — 200 con status forbidden', function () {
    ['set' => $setOther] = makeAthleteWithSet();

    $intruder = User::factory()->create();
    $intruder->assignRole('atleta');

    $this->actingAs($intruder)
        ->postJson(route('athlete.session.sync'), [
            'operations' => [[
                'client_uuid' => (string) Str::uuid(),
                'operation' => 'quick_log',
                'client_timestamp' => now()->getTimestampMs(),
                'payload' => ['set_id' => $setOther->id],
            ]],
        ])
        ->assertOk()
        ->assertJsonPath('results.0.status', 'forbidden');

    expect($setOther->fresh()->completed_at)->toBeNull();
});
