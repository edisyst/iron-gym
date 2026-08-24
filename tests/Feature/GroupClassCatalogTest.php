<?php

use App\Livewire\Backoffice\Calendar\GroupClassCatalog;
use App\Models\ClassOccurrence;
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
});

it('gestore visualizza il catalogo corsi', function () {
    GroupClass::factory()->create(['name' => 'Yoga', 'slug' => 'yoga']);

    Livewire::actingAs($this->gestore)
        ->test(GroupClassCatalog::class)
        ->assertSee('Yoga');
});

it('gestore crea un nuovo corso', function () {
    Livewire::actingAs($this->gestore)
        ->test(GroupClassCatalog::class)
        ->call('openForm')
        ->set('formName', 'Spinning')
        ->set('formDurationMinutes', 45)
        ->set('formDefaultCapacity', 15)
        ->set('formRoom', 'Sala B')
        ->call('save');

    expect(GroupClass::where('name', 'Spinning')->exists())->toBeTrue();
    expect(GroupClass::where('slug', 'spinning')->value('room'))->toBe('Sala B');
});

it('gestore modifica un corso esistente', function () {
    $gc = GroupClass::factory()->create(['name' => 'Pilates', 'slug' => 'pilates', 'duration_minutes' => 60]);

    Livewire::actingAs($this->gestore)
        ->test(GroupClassCatalog::class)
        ->call('openForm', $gc->id)
        ->set('formDurationMinutes', 75)
        ->call('save');

    expect($gc->fresh()->duration_minutes)->toBe(75);
});

it('gestore toglie e riattiva un corso', function () {
    $gc = GroupClass::factory()->create(['slug' => 'zumba', 'is_active' => true]);

    Livewire::actingAs($this->gestore)
        ->test(GroupClassCatalog::class)
        ->call('toggleActive', $gc->id);

    expect($gc->fresh()->is_active)->toBeFalse();

    Livewire::actingAs($this->gestore)
        ->test(GroupClassCatalog::class)
        ->call('toggleActive', $gc->id);

    expect($gc->fresh()->is_active)->toBeTrue();
});

it('gestore non può eliminare corso con occorrenze future', function () {
    $gc = GroupClass::factory()->create(['slug' => 'crossfit']);
    ClassOccurrence::factory()->create([
        'group_class_id' => $gc->id,
        'date' => today()->addDays(5)->toDateString(),
        'status' => 'planned',
    ]);

    Livewire::actingAs($this->gestore)
        ->test(GroupClassCatalog::class)
        ->call('deleteClass', $gc->id);

    // Corso non eliminato perché ha occorrenze future
    expect(GroupClass::find($gc->id))->not->toBeNull();
});

it('gestore elimina corso senza occorrenze future', function () {
    $gc = GroupClass::factory()->create(['slug' => 'boot-camp']);

    Livewire::actingAs($this->gestore)
        ->test(GroupClassCatalog::class)
        ->call('deleteClass', $gc->id);

    expect(GroupClass::find($gc->id))->toBeNull();
});

it('trainer non può creare corsi nel catalogo', function () {
    Livewire::actingAs($this->trainer)
        ->test(GroupClassCatalog::class)
        ->call('openForm')
        ->set('formName', 'Boxe')
        ->call('save')
        ->assertForbidden();
});

it('slug viene generato automaticamente con suffisso se già esistente', function () {
    GroupClass::factory()->create(['name' => 'Yoga', 'slug' => 'yoga']);

    Livewire::actingAs($this->gestore)
        ->test(GroupClassCatalog::class)
        ->call('openForm')
        ->set('formName', 'Yoga')
        ->call('save');

    expect(GroupClass::where('slug', 'yoga-1')->exists())->toBeTrue();
});
