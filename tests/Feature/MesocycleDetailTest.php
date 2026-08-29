<?php

use App\Livewire\Backoffice\Mesocycles\MesocycleDetail;
use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\User;
use App\Services\DeloadEvaluator;
use App\Services\WeeklyProgressionService;
use App\ValueObjects\DeloadSignal;
use App\ValueObjects\ProgressionResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Livewire\Livewire;
use Mockery\MockInterface;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'gestore', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);

    $athlete = User::factory()->create()->assignRole('atleta');
    $this->trainer = User::factory()->create()->assignRole('trainer');
    $this->gestore = User::factory()->create()->assignRole('gestore');

    $this->mesocycle = Mesocycle::factory()->create([
        'athlete_id' => $athlete->id,
        'trainer_id' => $this->trainer->id,
        'weeks_count' => 3,
    ]);
    $this->week1 = MicrocycleWeek::factory()->create([
        'mesocycle_id' => $this->mesocycle->id,
        'week_number' => 1,
        'is_deload' => false,
    ]);
    $this->week2 = MicrocycleWeek::factory()->create([
        'mesocycle_id' => $this->mesocycle->id,
        'week_number' => 2,
        'is_deload' => false,
    ]);
    $this->week3 = MicrocycleWeek::factory()->create([
        'mesocycle_id' => $this->mesocycle->id,
        'week_number' => 3,
        'is_deload' => false,
    ]);

    $this->instance(
        DeloadEvaluator::class,
        $this->mock(DeloadEvaluator::class, function (MockInterface $m) {
            $m->shouldReceive('evaluate')->andReturn(
                new DeloadSignal(activeTriggers: [], suggestedWeekNumber: null, notes: null)
            );
        })
    );
});

it('trainer assegnato può visualizzare il dettaglio mesociclo', function () {
    Livewire::actingAs($this->trainer)
        ->test(MesocycleDetail::class, ['mesocycleId' => $this->mesocycle->id])
        ->assertOk();
});

it('gestore può visualizzare qualsiasi mesociclo', function () {
    Livewire::actingAs($this->gestore)
        ->test(MesocycleDetail::class, ['mesocycleId' => $this->mesocycle->id])
        ->assertOk();
});

it('atleta viene respinto con 403', function () {
    $atleta = User::factory()->create()->assignRole('atleta');

    Livewire::actingAs($atleta)
        ->test(MesocycleDetail::class, ['mesocycleId' => $this->mesocycle->id])
        ->assertStatus(403);
});

it('forceDeload marca la settimana successiva come deload', function () {
    $this->instance(
        WeeklyProgressionService::class,
        $this->mock(WeeklyProgressionService::class, function (MockInterface $m) {
            $m->shouldReceive('progressWeek')->andReturn(
                new ProgressionResult(setsAddedByMuscle: [], feedbackTriggers: [], action: 'deload', note: null)
            );
        })
    );

    Livewire::actingAs($this->trainer)
        ->test(MesocycleDetail::class, ['mesocycleId' => $this->mesocycle->id])
        ->set('selectedWeekNumber', 1)
        ->call('forceDeload');

    expect($this->week2->fresh()->is_deload)->toBeTrue();
});

it('forceDeload mostra errore se non esiste settimana successiva', function () {
    $this->instance(
        WeeklyProgressionService::class,
        $this->mock(WeeklyProgressionService::class, fn (MockInterface $m) => null)
    );

    Livewire::actingAs($this->trainer)
        ->test(MesocycleDetail::class, ['mesocycleId' => $this->mesocycle->id])
        ->set('selectedWeekNumber', 3)
        ->call('forceDeload');

    expect($this->week3->fresh()->is_deload)->toBeFalse();
});

it('applyProgression chiama il servizio e aggiorna lastProgressionResultData', function () {
    Feature::for($this->trainer)->activate('periodization_engine');

    $this->instance(
        WeeklyProgressionService::class,
        $this->mock(WeeklyProgressionService::class, function (MockInterface $m) {
            $m->shouldReceive('progressWeek')->once()->andReturn(
                new ProgressionResult(
                    setsAddedByMuscle: ['quadriceps' => 1],
                    feedbackTriggers: [],
                    action: 'increase',
                    note: null
                )
            );
        })
    );

    $component = Livewire::actingAs($this->trainer)
        ->test(MesocycleDetail::class, ['mesocycleId' => $this->mesocycle->id])
        ->set('selectedWeekNumber', 1)
        ->call('applyProgression');

    expect($component->get('lastProgressionResultData'))->not->toBeNull()
        ->and($component->get('lastProgressionResultData')['action'])->toBe('increase');
});

it('applyProgression nega 403 se periodization_engine disattivo', function () {
    Feature::for($this->trainer)->deactivate('periodization_engine');

    $this->instance(
        WeeklyProgressionService::class,
        $this->mock(WeeklyProgressionService::class, fn (MockInterface $m) => null)
    );

    Livewire::actingAs($this->trainer)
        ->test(MesocycleDetail::class, ['mesocycleId' => $this->mesocycle->id])
        ->set('selectedWeekNumber', 1)
        ->call('applyProgression')
        ->assertForbidden();
});
