<?php

use App\Livewire\Backoffice\Access\QuickCheckin;
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
    Role::firstOrCreate(['name' => 'gestore', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);

    $this->gestore = User::factory()->create()->assignRole('gestore');
    $this->receptionist = User::factory()->create()->assignRole('receptionist');
});

it('gestore può accedere al check-in rapido', function () {
    Livewire::actingAs($this->gestore)
        ->test(QuickCheckin::class)
        ->assertOk();
});

it('receptionist può accedere al check-in rapido', function () {
    Livewire::actingAs($this->receptionist)
        ->test(QuickCheckin::class)
        ->assertOk();
});

it('trainer non può accedere al check-in rapido', function () {
    $trainer = User::factory()->create()->assignRole('trainer');

    $this->actingAs($trainer)
        ->get(route('backoffice.checkin'))
        ->assertForbidden();
});

it('registra accesso per tesserato con abbonamento attivo e cert valido', function () {
    $creator = User::factory()->create();
    $plan = SubscriptionPlan::factory()->create();
    $member = Member::factory()->create([
        'medical_cert_expiry' => now()->addMonths(6)->toDateString(),
    ]);

    Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'started_at' => now()->subDays(10)->toDateString(),
        'expires_at' => now()->addDays(20)->toDateString(),
        'accesses_remaining' => null,
        'created_by' => $creator->id,
    ]);

    Livewire::actingAs($this->receptionist)
        ->test(QuickCheckin::class)
        ->call('selectMember', $member->id)
        ->call('registerAccess')
        ->assertSee('Accesso registrato per');

    expect(AccessLog::where('member_id', $member->id)->count())->toBe(1);
});

it('rifiuta check-in se certificato medico scaduto', function () {
    $creator = User::factory()->create();
    $plan = SubscriptionPlan::factory()->create();
    $member = Member::factory()->create([
        'medical_cert_expiry' => now()->subDay()->toDateString(),
    ]);

    Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'started_at' => now()->subDays(10)->toDateString(),
        'expires_at' => now()->addDays(20)->toDateString(),
        'created_by' => $creator->id,
    ]);

    Livewire::actingAs($this->receptionist)
        ->test(QuickCheckin::class)
        ->call('selectMember', $member->id)
        ->call('registerAccess')
        ->assertSee('Certificato medico scaduto o mancante');

    expect(AccessLog::where('member_id', $member->id)->count())->toBe(0);
});

it('rifiuta check-in se nessun abbonamento attivo', function () {
    $member = Member::factory()->create([
        'medical_cert_expiry' => now()->addMonths(6)->toDateString(),
    ]);

    Livewire::actingAs($this->receptionist)
        ->test(QuickCheckin::class)
        ->call('selectMember', $member->id)
        ->call('registerAccess')
        ->assertSee('Nessun abbonamento attivo');

    expect(AccessLog::where('member_id', $member->id)->count())->toBe(0);
});

it('mostra accessi odierni nella cronologia', function () {
    $member = Member::factory()->create(['last_name' => 'Ferro', 'first_name' => 'Luigi']);

    AccessLog::create([
        'member_id' => $member->id,
        'subscription_id' => null,
        'checked_in_at' => now(),
        'checked_in_by' => $this->gestore->id,
    ]);

    Livewire::actingAs($this->gestore)
        ->test(QuickCheckin::class)
        ->assertSee('Ferro');
});
