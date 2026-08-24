<?php

use App\Livewire\Backoffice\Reports\ManagerDashboard as BackofficeManagerDashboard;
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

it('gestore può visualizzare la dashboard manager', function () {
    Livewire::actingAs($this->gestore)
        ->test(BackofficeManagerDashboard::class)
        ->assertOk();
});

it('dashboard manager inizializza dateFrom e dateTo del mese corrente', function () {
    $component = Livewire::actingAs($this->gestore)
        ->test(BackofficeManagerDashboard::class);

    expect($component->get('dateFrom'))->toBe(now()->startOfMonth()->toDateString())
        ->and($component->get('dateTo'))->toBe(now()->endOfMonth()->toDateString());
});
