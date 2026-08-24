<?php

use App\Livewire\Backoffice\Dashboard;
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
    $this->gestore = User::factory()->create()->assignRole('gestore');
});

it('widget scadenze visibile quando ci sono certificati in scadenza entro 30 giorni', function () {
    Member::factory()->create([
        'is_active' => true,
        'medical_cert_expiry' => now()->addDays(10)->toDateString(),
    ]);

    Livewire::actingAs($this->gestore)
        ->test(Dashboard::class)
        ->assertSee('Scadenze imminenti');
});

it('widget scadenze non visibile quando non ci sono scadenze imminenti', function () {
    Member::factory()->create([
        'is_active' => true,
        'medical_cert_expiry' => now()->addDays(90)->toDateString(),
    ]);

    Livewire::actingAs($this->gestore)
        ->test(Dashboard::class)
        ->assertDontSee('Scadenze imminenti');
});

it('certExpiring30Count conta solo certificati futuri entro 30 giorni', function () {
    // Incluso: scade tra 10 giorni
    Member::factory()->create([
        'is_active' => true,
        'medical_cert_expiry' => now()->addDays(10)->toDateString(),
    ]);
    // Escluso: già scaduto ieri
    Member::factory()->create([
        'is_active' => true,
        'medical_cert_expiry' => now()->subDay()->toDateString(),
    ]);
    // Escluso: scade tra 60 giorni
    Member::factory()->create([
        'is_active' => true,
        'medical_cert_expiry' => now()->addDays(60)->toDateString(),
    ]);

    $component = Livewire::actingAs($this->gestore)->test(Dashboard::class);

    expect($component->get('certExpiring30Count'))->toBe(1);
});

it('subExpiring7Count conta solo abbonamenti attivi in scadenza entro 7 giorni', function () {
    $creator = User::factory()->create();
    $plan = SubscriptionPlan::factory()->create();

    // Incluso: scade tra 5 giorni
    Subscription::factory()->create([
        'member_id' => Member::factory()->create()->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'started_at' => now()->subDays(25)->toDateString(),
        'expires_at' => now()->addDays(5)->toDateString(),
        'created_by' => $creator->id,
    ]);
    // Escluso: scade tra 20 giorni
    Subscription::factory()->create([
        'member_id' => Member::factory()->create()->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'started_at' => now()->subDays(10)->toDateString(),
        'expires_at' => now()->addDays(20)->toDateString(),
        'created_by' => $creator->id,
    ]);

    $component = Livewire::actingAs($this->gestore)->test(Dashboard::class);

    expect($component->get('subExpiring7Count'))->toBe(1);
});
