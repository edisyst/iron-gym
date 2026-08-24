<?php

use App\Livewire\Backoffice\Search\GlobalSearch;
use App\Models\Member;
use App\Models\User;
use App\Models\WorkoutTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'gestore', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);

    $this->gestore = User::factory()->create()->assignRole('gestore');
});

it('query sotto 2 caratteri non restituisce risultati', function () {
    User::factory()->create(['name' => 'Mario Rossi'])->assignRole('atleta');

    $component = Livewire::actingAs($this->gestore)
        ->test(GlobalSearch::class)
        ->set('query', 'M');

    expect($component->viewData('athletes'))->toBeEmpty();
});

it('trova atleta per nome', function () {
    $user = User::factory()->create(['name' => 'Filippo Neri'])->assignRole('atleta');
    Member::factory()->create(['user_id' => $user->id, 'first_name' => 'Filippo', 'last_name' => 'Neri']);

    $component = Livewire::actingAs($this->gestore)
        ->test(GlobalSearch::class)
        ->set('query', 'Filippo');

    expect($component->viewData('athletes'))->toHaveCount(1);
});

it('trova trainer per nome', function () {
    User::factory()->create(['name' => 'Carlo Bianchi'])->assignRole('trainer');

    $component = Livewire::actingAs($this->gestore)
        ->test(GlobalSearch::class)
        ->set('query', 'Carlo');

    expect($component->viewData('trainers'))->toHaveCount(1);
});

it('trova template per nome', function () {
    WorkoutTemplate::factory()->create(['name' => 'PPL Avanzato']);

    $component = Livewire::actingAs($this->gestore)
        ->test(GlobalSearch::class)
        ->set('query', 'PPL');

    expect($component->viewData('templates'))->toHaveCount(1);
});
