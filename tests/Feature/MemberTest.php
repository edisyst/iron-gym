<?php

use App\Models\AccessLog;
use App\Models\Member;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('un tesserato può essere creato con dati validi', function () {
    $member = Member::create([
        'first_name' => 'Mario',
        'last_name' => 'Test',
        'email' => 'mario.test@example.com',
        'is_active' => true,
    ]);

    expect($member->id)->toBeInt()
        ->and($member->email)->toBe('mario.test@example.com')
        ->and($member->is_active)->toBeTrue();

    $this->assertDatabaseHas('members', ['email' => 'mario.test@example.com']);
});

it('un tesserato con email duplicata viene rifiutato', function () {
    Member::create([
        'first_name' => 'Mario',
        'last_name' => 'Primo',
        'email' => 'duplicato@example.com',
        'is_active' => true,
    ]);

    expect(fn () => Member::create([
        'first_name' => 'Luigi',
        'last_name' => 'Secondo',
        'email' => 'duplicato@example.com',
        'is_active' => true,
    ]))->toThrow(QueryException::class);
});

it('la registrazione accesso incrementa accesses_used sull\'abbonamento', function () {
    $member = Member::create([
        'first_name' => 'Test',
        'last_name' => 'Accesso',
        'email' => 'test.accesso@example.com',
        'is_active' => true,
    ]);

    $plan = SubscriptionPlan::create([
        'name' => 'Test Plan',
        'price_cents' => 1000,
        'duration_days' => 30,
    ]);

    $subscription = Subscription::create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'started_at' => today()->toDateString(),
        'expires_at' => today()->addDays(30)->toDateString(),
        'status' => 'active',
    ]);

    $subscription->increment('accesses_used');
    AccessLog::create([
        'member_id' => $member->id,
        'subscription_id' => $subscription->id,
        'checked_in_at' => now(),
    ]);

    expect($subscription->fresh()->accesses_used)->toBe(1);
    $this->assertDatabaseHas('access_logs', ['member_id' => $member->id]);
});

it('la registrazione accesso fallisce se non c\'è abbonamento attivo', function () {
    $member = Member::create([
        'first_name' => 'Senza',
        'last_name' => 'Abbonamento',
        'email' => 'senza.abb@example.com',
        'is_active' => true,
    ]);

    $activeSubscription = Subscription::where('member_id', $member->id)->active()->first();

    expect($activeSubscription)->toBeNull();
});
