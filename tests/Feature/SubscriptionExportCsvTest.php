<?php

use App\Models\Member;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'gestore', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);

    $this->gestore = User::factory()->create()->assignRole('gestore');
    $this->receptionist = User::factory()->create()->assignRole('receptionist');

    $plan = SubscriptionPlan::factory()->create(['name' => 'Piano Test']);
    $member = Member::factory()->create(['last_name' => 'Bianchi', 'first_name' => 'Carlo']);

    $this->sub = Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'started_at' => '2026-01-01',
        'expires_at' => '2026-12-31',
        'created_by' => $this->gestore->id,
    ]);
});

it('gestore scarica CSV abbonamenti', function () {
    $response = $this->actingAs($this->gestore)
        ->get(route('backoffice.subscriptions.export'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

it('CSV contiene dati tesserato e piano', function () {
    $response = $this->actingAs($this->gestore)
        ->get(route('backoffice.subscriptions.export'));

    $content = $response->streamedContent();

    expect($content)->toContain('Bianchi')
        ->and($content)->toContain('Piano Test');
});

it('receptionist non può scaricare CSV', function () {
    $this->actingAs($this->receptionist)
        ->get(route('backoffice.subscriptions.export'))
        ->assertForbidden();
});

it('filtro active esclude abbonamenti scaduti dal CSV', function () {
    $plan = SubscriptionPlan::factory()->create(['name' => 'Piano Vecchio']);
    $member2 = Member::factory()->create(['last_name' => 'Scaduto-Test']);

    Subscription::factory()->create([
        'member_id' => $member2->id,
        'plan_id' => $plan->id,
        'status' => 'expired',
        'started_at' => '2025-01-01',
        'expires_at' => '2025-06-01',
        'created_by' => $this->gestore->id,
    ]);

    $response = $this->actingAs($this->gestore)
        ->get(route('backoffice.subscriptions.export', ['filter' => 'active']));

    $content = $response->streamedContent();

    expect($content)->toContain('Bianchi')
        ->and($content)->not->toContain('Scaduto-Test');
});
