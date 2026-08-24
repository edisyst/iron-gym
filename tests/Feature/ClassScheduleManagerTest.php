<?php

use App\Livewire\Backoffice\Calendar\ClassScheduleManager;
use App\Models\ClassOccurrence;
use App\Models\ClassSchedule;
use App\Models\GroupClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'gestore', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);

    $this->gestore = User::factory()->create()->assignRole('gestore');
    $this->trainer = User::factory()->create()->assignRole('trainer');

    $this->groupClass = GroupClass::factory()->create(['is_active' => true]);
});

it('mostra la lista dei palinsesti', function () {
    ClassSchedule::factory()->create([
        'group_class_id' => $this->groupClass->id,
        'weekday' => 0,
        'start_time' => '09:00:00',
        'trainer_id' => $this->trainer->id,
    ]);

    Livewire::actingAs($this->gestore)
        ->test(ClassScheduleManager::class)
        ->assertSee($this->groupClass->name);
});

it('crea un nuovo palinsesto', function () {
    Livewire::actingAs($this->gestore)
        ->test(ClassScheduleManager::class)
        ->call('openForm')
        ->set('formGroupClassId', $this->groupClass->id)
        ->set('formWeekday', 1)
        ->set('formStartTime', '10:00')
        ->set('formTrainerId', $this->trainer->id)
        ->set('formValidFrom', today()->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    expect(ClassSchedule::where('group_class_id', $this->groupClass->id)->count())->toBe(1);
    $schedule = ClassSchedule::first();
    expect($schedule->weekday)->toBe(1);
    expect($schedule->start_time)->toBe('10:00:00');
    expect($schedule->is_active)->toBeTrue();
});

it('valida i campi obbligatori', function () {
    Livewire::actingAs($this->gestore)
        ->test(ClassScheduleManager::class)
        ->call('openForm')
        ->set('formGroupClassId', 0)
        ->set('formStartTime', '')
        ->call('save')
        ->assertHasErrors(['formGroupClassId', 'formStartTime']);
});

it('modifica un palinsesto esistente', function () {
    $schedule = ClassSchedule::factory()->create([
        'group_class_id' => $this->groupClass->id,
        'weekday' => 0,
        'start_time' => '09:00:00',
        'trainer_id' => $this->trainer->id,
        'is_active' => true,
    ]);

    Livewire::actingAs($this->gestore)
        ->test(ClassScheduleManager::class)
        ->call('openForm', $schedule->id)
        ->assertSet('formWeekday', 0)
        ->assertSet('formStartTime', '09:00')
        ->set('formStartTime', '11:00')
        ->call('save')
        ->assertHasNoErrors();

    expect($schedule->fresh()->start_time)->toBe('11:00:00');
});

it('disattiva e riattiva un palinsesto', function () {
    $schedule = ClassSchedule::factory()->create([
        'group_class_id' => $this->groupClass->id,
        'trainer_id' => $this->trainer->id,
        'is_active' => true,
    ]);

    Livewire::actingAs($this->gestore)
        ->test(ClassScheduleManager::class)
        ->call('toggleActive', $schedule->id);

    expect($schedule->fresh()->is_active)->toBeFalse();

    Livewire::actingAs($this->gestore)
        ->test(ClassScheduleManager::class)
        ->call('toggleActive', $schedule->id);

    expect($schedule->fresh()->is_active)->toBeTrue();
});

it('elimina un palinsesto senza occorrenze future', function () {
    $schedule = ClassSchedule::factory()->create([
        'group_class_id' => $this->groupClass->id,
        'trainer_id' => $this->trainer->id,
    ]);

    Livewire::actingAs($this->gestore)
        ->test(ClassScheduleManager::class)
        ->call('deleteSchedule', $schedule->id);

    expect(ClassSchedule::find($schedule->id))->toBeNull();
});

it('blocca l\'eliminazione se ci sono occorrenze future pianificate', function () {
    $schedule = ClassSchedule::factory()->create([
        'group_class_id' => $this->groupClass->id,
        'trainer_id' => $this->trainer->id,
    ]);

    ClassOccurrence::factory()->create([
        'class_schedule_id' => $schedule->id,
        'group_class_id' => $this->groupClass->id,
        'trainer_id' => $this->trainer->id,
        'date' => today()->addDays(7)->toDateString(),
        'status' => 'planned',
    ]);

    Livewire::actingAs($this->gestore)
        ->test(ClassScheduleManager::class)
        ->call('deleteSchedule', $schedule->id);

    expect(ClassSchedule::find($schedule->id))->not->toBeNull();
});

it('trainer può creare palinsesti', function () {
    Livewire::actingAs($this->trainer)
        ->test(ClassScheduleManager::class)
        ->call('openForm')
        ->set('formGroupClassId', $this->groupClass->id)
        ->set('formWeekday', 2)
        ->set('formStartTime', '18:00')
        ->set('formTrainerId', $this->trainer->id)
        ->set('formValidFrom', today()->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    expect(ClassSchedule::count())->toBe(1);
});
