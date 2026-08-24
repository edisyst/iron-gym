<?php

use App\Livewire\Backoffice\Members\ExpiryDashboard;
use App\Models\Member;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'gestore', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);

    $this->gestore = User::factory()->create()->assignRole('gestore');
    $this->receptionist = User::factory()->create()->assignRole('receptionist');
});

it('gestore può accedere al pannello scadenze', function () {
    Livewire::actingAs($this->gestore)
        ->test(ExpiryDashboard::class)
        ->assertOk();
});

it('receptionist può accedere al pannello scadenze', function () {
    Livewire::actingAs($this->receptionist)
        ->test(ExpiryDashboard::class)
        ->assertOk();
});

it('atleta non può accedere al pannello scadenze', function () {
    $atleta = User::factory()->create()->assignRole('atleta');

    $this->actingAs($atleta)
        ->get(route('backoffice.members.expiry'))
        ->assertForbidden();
});

it('mostra certificato medico in scadenza entro 30 giorni', function () {
    $member = Member::factory()->create([
        'first_name' => 'Luca',
        'last_name' => 'Bianchi',
        'medical_cert_expiry' => now()->addDays(10)->toDateString(),
    ]);

    Livewire::actingAs($this->gestore)
        ->test(ExpiryDashboard::class)
        ->assertSee('Bianchi');
});

it('non mostra certificato medico con scadenza oltre la finestra', function () {
    Member::factory()->create([
        'first_name' => 'Mario',
        'last_name' => 'Verdi',
        'medical_cert_expiry' => now()->addDays(60)->toDateString(),
    ]);

    Livewire::actingAs($this->gestore)
        ->test(ExpiryDashboard::class)
        ->assertDontSee('Verdi');
});

it('mostra abbonamento in scadenza entro 7 giorni', function () {
    $creator = User::factory()->create();
    $member = Member::factory()->create(['last_name' => 'Rossi-Scadenza']);
    $plan = SubscriptionPlan::factory()->create();

    Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'started_at' => now()->subDays(20)->toDateString(),
        'expires_at' => now()->addDays(5)->toDateString(),
        'created_by' => $creator->id,
    ]);

    Livewire::actingAs($this->gestore)
        ->test(ExpiryDashboard::class)
        ->assertSee('Rossi-Scadenza');
});

it('filtro certDays esclude certificati oltre la finestra ridotta', function () {
    Member::factory()->create([
        'last_name' => 'Fuori-Finestra',
        'medical_cert_expiry' => now()->addDays(20)->toDateString(),
    ]);

    Livewire::actingAs($this->gestore)
        ->test(ExpiryDashboard::class)
        ->set('certDays', 7)
        ->assertDontSee('Fuori-Finestra');
});
