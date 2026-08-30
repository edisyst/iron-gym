<?php

use App\Livewire\Backoffice\Mesocycles\MesocycleDetail;
use App\Livewire\Backoffice\Mesocycles\MesocycleList;
use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'gestore', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);

    $athlete = User::factory()->create()->assignRole('atleta');

    $this->trainerA = User::factory()->create()->assignRole('trainer');
    $this->trainerB = User::factory()->create()->assignRole('trainer');
    $this->gestore = User::factory()->create()->assignRole('gestore');

    $this->mesoA = Mesocycle::factory()->create([
        'athlete_id' => $athlete->id,
        'trainer_id' => $this->trainerA->id,
        'name' => 'Mesociclo di A',
        'weeks_count' => 2,
    ]);

    $this->mesoB = Mesocycle::factory()->create([
        'athlete_id' => $athlete->id,
        'trainer_id' => $this->trainerB->id,
        'name' => 'Mesociclo di B',
        'weeks_count' => 2,
    ]);

    foreach ([$this->mesoA, $this->mesoB] as $meso) {
        foreach ([1, 2] as $n) {
            MicrocycleWeek::factory()->create([
                'mesocycle_id' => $meso->id,
                'week_number' => $n,
                'is_deload' => false,
            ]);
        }
    }
});

it('il trainer vede in lista solo i propri mesocicli', function () {
    Livewire::actingAs($this->trainerA)
        ->test(MesocycleList::class)
        ->assertSee('Mesociclo di A')
        ->assertDontSee('Mesociclo di B');
});

it('il gestore vede i mesocicli di tutti i trainer', function () {
    Livewire::actingAs($this->gestore)
        ->test(MesocycleList::class)
        ->assertSee('Mesociclo di A')
        ->assertSee('Mesociclo di B');
});

it('nega applyProgression al trainer su un mesociclo non suo', function () {
    Livewire::actingAs($this->trainerB)
        ->test(MesocycleDetail::class, ['mesocycleId' => $this->mesoB->id])
        ->set('mesocycleId', $this->mesoA->id)
        ->call('applyProgression')
        ->assertForbidden();
});

it('nega forceDeload al trainer su un mesociclo non suo', function () {
    Livewire::actingAs($this->trainerB)
        ->test(MesocycleDetail::class, ['mesocycleId' => $this->mesoB->id])
        ->set('mesocycleId', $this->mesoA->id)
        ->call('forceDeload')
        ->assertForbidden();
});

it('consente al gestore di agire su qualsiasi mesociclo', function () {
    Livewire::actingAs($this->gestore)
        ->test(MesocycleDetail::class, ['mesocycleId' => $this->mesoA->id])
        ->call('forceDeload')
        ->assertOk();

    expect($this->mesoA->weeks()->where('week_number', 2)->value('is_deload'))->toBeTrue();
});
