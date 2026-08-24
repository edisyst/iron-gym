<?php

use App\Livewire\Athlete\Profile;
use App\Models\AccessLog;
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
});

it('tab accessi visibile nel profilo atleta', function () {
    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->assertSee('Accessi');
});

it('tab accessi mostra ingresso registrato', function () {
    AccessLog::create([
        'member_id' => $this->member->id,
        'subscription_id' => null,
        'checked_in_at' => now()->subHours(2),
        'checked_in_by' => null,
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'accessi')
        ->assertSee('Entrata');
});

it('tab accessi mostra piano abbonamento', function () {
    $creator = User::factory()->create();
    $plan = SubscriptionPlan::factory()->create(['name' => 'Piano Mensile Test']);
    $sub = Subscription::factory()->create([
        'member_id' => $this->member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'started_at' => now()->subDays(10)->toDateString(),
        'expires_at' => now()->addDays(20)->toDateString(),
        'created_by' => $creator->id,
    ]);

    AccessLog::create([
        'member_id' => $this->member->id,
        'subscription_id' => $sub->id,
        'checked_in_at' => now()->subHour(),
        'checked_in_by' => null,
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'accessi')
        ->assertSee('Piano Mensile Test');
});

it('tab accessi mostra stato vuoto senza accessi', function () {
    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'accessi')
        ->assertSee('Nessun accesso registrato');
});

it('tab accessi non mostra accessi di altri tesserati', function () {
    $otherMember = Member::factory()->create();

    AccessLog::create([
        'member_id' => $otherMember->id,
        'subscription_id' => null,
        'checked_in_at' => now()->subHour(),
        'checked_in_by' => null,
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'accessi')
        ->assertSee('Nessun accesso registrato');
});
