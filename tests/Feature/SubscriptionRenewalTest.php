<?php

use App\Livewire\Backoffice\Subscriptions\SubscriptionForm;
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
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);

    $this->gestore = User::factory()->create()->assignRole('gestore');
    $this->receptionist = User::factory()->create()->assignRole('receptionist');

    $this->member = Member::factory()->create();
    $this->plan = SubscriptionPlan::factory()->create(['duration_days' => 30]);
    $this->sub = Subscription::factory()->create([
        'member_id' => $this->member->id,
        'plan_id' => $this->plan->id,
        'status' => 'active',
        'started_at' => now()->subDays(25)->toDateString(),
        'expires_at' => now()->addDays(5)->toDateString(),
        'created_by' => $this->gestore->id,
    ]);
});

it('bottone Rinnova visibile a gestore nella lista abbonamenti', function () {
    Livewire::actingAs($this->gestore)
        ->test(SubscriptionList::class)
        ->assertSee('Rinnova');
});

it('bottone Rinnova non visibile a trainer nella lista abbonamenti', function () {
    $trainer = User::factory()->create()->assignRole('trainer');

    Livewire::actingAs($trainer)
        ->test(SubscriptionList::class)
        ->assertDontSee('Rinnova');
});

it('form pre-popola member_id da query string', function () {
    $component = Livewire::actingAs($this->gestore)
        ->withQueryParams(['member_id' => $this->member->id])
        ->test(SubscriptionForm::class);

    expect($component->get('member_id'))->toBe((string) $this->member->id);
});

it('form pre-popola plan_id e calcola expires_at da query string', function () {
    $component = Livewire::actingAs($this->gestore)
        ->withQueryParams(['member_id' => $this->member->id, 'plan_id' => $this->plan->id])
        ->test(SubscriptionForm::class);

    expect($component->get('plan_id'))->toBe((string) $this->plan->id);

    $expectedExpiry = today()->addDays(30)->format('Y-m-d');
    expect($component->get('expires_at'))->toBe($expectedExpiry);
});

it('rinnovo crea nuovo abbonamento con stessi piano e tesserato', function () {
    Livewire::actingAs($this->gestore)
        ->withQueryParams(['member_id' => $this->member->id, 'plan_id' => $this->plan->id])
        ->test(SubscriptionForm::class)
        ->call('save');

    expect(Subscription::where('member_id', $this->member->id)->count())->toBe(2);
});
