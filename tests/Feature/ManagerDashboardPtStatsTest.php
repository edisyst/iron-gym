<?php

use App\Livewire\Backoffice\Reports\ManagerDashboard;
use App\Models\Member;
use App\Models\PtBooking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'gestore', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);

    $this->gestore = User::factory()->create()->assignRole('gestore');
    $this->trainer = User::factory()->create(['name' => 'Mario Rossi'])->assignRole('trainer');
    $this->member = Member::factory()->create();
});

it('dashboard mostra tabella sessioni PT per trainer', function () {
    PtBooking::factory()->create([
        'trainer_id' => $this->trainer->id,
        'member_id' => $this->member->id,
        'booked_date' => now()->toDateString(),
        'status' => 'completed',
    ]);

    Livewire::actingAs($this->gestore)
        ->test(ManagerDashboard::class)
        ->assertSee('Sessioni PT completate per trainer')
        ->assertSee('Mario Rossi')
        ->assertSee('1');
});

it('sessioni non completed escluse dal conteggio', function () {
    PtBooking::factory()->create([
        'trainer_id' => $this->trainer->id,
        'member_id' => $this->member->id,
        'booked_date' => now()->toDateString(),
        'status' => 'confirmed',
    ]);

    Livewire::actingAs($this->gestore)
        ->test(ManagerDashboard::class)
        ->assertSee('Nessuna sessione PT nel periodo');
});

it('filtro periodo esclude sessioni fuori range', function () {
    PtBooking::factory()->create([
        'trainer_id' => $this->trainer->id,
        'member_id' => $this->member->id,
        'booked_date' => now()->subMonths(2)->toDateString(),
        'status' => 'completed',
    ]);

    Livewire::actingAs($this->gestore)
        ->test(ManagerDashboard::class)
        ->assertSee('Nessuna sessione PT nel periodo');
});
