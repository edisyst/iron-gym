<?php

use App\Livewire\Athlete\Profile as AthleteProfile;
use App\Models\Member;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);

    $this->athleteUser = User::factory()->create()->assignRole('atleta');
    $this->member = Member::factory()->create(['user_id' => $this->athleteUser->id]);

    $this->plan = SubscriptionPlan::create([
        'name' => 'Mensile',
        'price_cents' => 4000,
        'duration_days' => 30,
        'is_active' => true,
    ]);
});

it('mostra nome piano e scadenza abbonamento attivo', function () {
    Subscription::factory()->create([
        'member_id' => $this->member->id,
        'plan_id' => $this->plan->id,
        'status' => 'active',
        'started_at' => now()->subDays(10),
        'expires_at' => now()->addDays(20),
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(AthleteProfile::class)
        ->set('activeSection', 'abbonamento')
        ->assertSee('Mensile')
        ->assertSee('Attivo');
});

it('mostra messaggio senza abbonamento attivo', function () {
    Livewire::actingAs($this->athleteUser)
        ->test(AthleteProfile::class)
        ->set('activeSection', 'abbonamento')
        ->assertSee('Nessun abbonamento attivo');
});

it('mostra badge in scadenza se scade entro 14 giorni', function () {
    Subscription::factory()->create([
        'member_id' => $this->member->id,
        'plan_id' => $this->plan->id,
        'status' => 'active',
        'started_at' => now()->subDays(20),
        'expires_at' => now()->addDays(7),
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(AthleteProfile::class)
        ->set('activeSection', 'abbonamento')
        ->assertSee('In scadenza');
});

it('abbonamento scaduto mostra badge Scaduto', function () {
    Subscription::factory()->create([
        'member_id' => $this->member->id,
        'plan_id' => $this->plan->id,
        'status' => 'active',
        'started_at' => now()->subDays(40),
        'expires_at' => now()->subDays(5),
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(AthleteProfile::class)
        ->set('activeSection', 'abbonamento')
        ->assertSee('Scaduto');
});
