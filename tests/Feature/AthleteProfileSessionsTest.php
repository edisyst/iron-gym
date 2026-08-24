<?php

use App\Livewire\Athlete\Profile;
use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);

    $this->athleteUser = User::factory()->create()->assignRole('atleta');

    $this->mesocycle = Mesocycle::factory()->create(['athlete_id' => $this->athleteUser->id]);
    $this->week = MicrocycleWeek::factory()->create(['mesocycle_id' => $this->mesocycle->id]);
});

it('tab sessioni mostra sessione completata', function () {
    TrainingSession::factory()->create([
        'microcycle_week_id'      => $this->week->id,
        'name'         => 'Push A',
        'status'       => 'completed',
        'completed_at' => now()->subDays(1),
        'started_at'   => now()->subDays(1)->subHour(),
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'sessioni')
        ->assertSee('Push A')
        ->assertSee('Completata');
});

it('tab sessioni mostra sessione saltata', function () {
    TrainingSession::factory()->create([
        'microcycle_week_id'      => $this->week->id,
        'name'         => 'Pull B',
        'status'       => 'skipped',
        'completed_at' => now()->subDays(2),
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'sessioni')
        ->assertSee('Pull B')
        ->assertSee('Saltata');
});

it('tab sessioni non mostra sessioni planned', function () {
    TrainingSession::factory()->create([
        'microcycle_week_id' => $this->week->id,
        'name'    => 'Legs C',
        'status'  => 'planned',
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'sessioni')
        ->assertSee('Nessuna sessione completata');
});

it('tab sessioni mostra durata se disponibile', function () {
    TrainingSession::factory()->create([
        'microcycle_week_id'      => $this->week->id,
        'name'         => 'Push A',
        'status'       => 'completed',
        'started_at'   => now()->subDays(1)->setTime(10, 0),
        'completed_at' => now()->subDays(1)->setTime(11, 15),
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'sessioni')
        ->assertSee('75 min');
});

it('tab sessioni non mostra sessioni di altri atleti', function () {
    $otherUser = User::factory()->create()->assignRole('atleta');
    $otherMeso = Mesocycle::factory()->create(['athlete_id' => $otherUser->id]);
    $otherWeek = MicrocycleWeek::factory()->create(['mesocycle_id' => $otherMeso->id]);

    TrainingSession::factory()->create([
        'microcycle_week_id'      => $otherWeek->id,
        'name'         => 'Sessione altrui',
        'status'       => 'completed',
        'completed_at' => now()->subDays(1),
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'sessioni')
        ->assertDontSee('Sessione altrui');
});
