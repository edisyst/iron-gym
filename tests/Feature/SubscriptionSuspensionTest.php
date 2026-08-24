<?php

use App\Livewire\Backoffice\Subscriptions\SubscriptionList;
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

    $this->gestore = User::factory()->create()->assignRole('gestore');
    $this->receptionist = User::factory()->create()->assignRole('receptionist');

    $plan = SubscriptionPlan::factory()->create();
    $member = Member::factory()->create();

    $this->activeSub = Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'started_at' => now()->subDays(10)->toDateString(),
        'expires_at' => now()->addDays(20)->toDateString(),
        'created_by' => $this->gestore->id,
    ]);
});

it('gestore può sospendere abbonamento attivo', function () {
    Livewire::actingAs($this->gestore)
        ->test(SubscriptionList::class)
        ->call('suspend', $this->activeSub->id);

    expect($this->activeSub->fresh()->status)->toBe('suspended');
});

it('gestore può riattivare abbonamento sospeso', function () {
    $this->activeSub->update(['status' => 'suspended']);

    Livewire::actingAs($this->gestore)
        ->test(SubscriptionList::class)
        ->call('reactivate', $this->activeSub->id);

    expect($this->activeSub->fresh()->status)->toBe('active');
});

it('receptionist non può sospendere abbonamento', function () {
    // Livewire inghiotte abort() — verifichiamo che lo status non cambi
    try {
        Livewire::actingAs($this->receptionist)
            ->test(SubscriptionList::class)
            ->call('suspend', $this->activeSub->id);
    } catch (Throwable) {
    }

    expect($this->activeSub->fresh()->status)->toBe('active');
});

it('filtro sospesi mostra solo abbonamenti sospesi', function () {
    $this->activeSub->update(['status' => 'suspended']);

    Livewire::actingAs($this->gestore)
        ->test(SubscriptionList::class)
        ->set('filter', 'suspended')
        ->assertSee('Sospeso');
});

it('sospensione di abbonamento già sospeso non cambia status', function () {
    $this->activeSub->update(['status' => 'suspended']);

    try {
        Livewire::actingAs($this->gestore)
            ->test(SubscriptionList::class)
            ->call('suspend', $this->activeSub->id);
    } catch (Throwable) {
    }

    expect($this->activeSub->fresh()->status)->toBe('suspended');
});
