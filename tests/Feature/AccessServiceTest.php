<?php

use App\Models\AccessLog;
use App\Models\Member;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\AccessService;
use App\Services\CheckinFailure;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(AccessService::class);
    $this->performer = User::factory()->create();
    $this->plan = SubscriptionPlan::factory()->create();
});

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

function memberWithActiveSub(array $memberOverrides = [], array $subOverrides = []): Member
{
    $member = Member::factory()->create(array_merge([
        'medical_cert_expiry' => now()->addYear()->toDateString(),
    ], $memberOverrides));

    Subscription::factory()->create(array_merge([
        'member_id' => $member->id,
        'plan_id' => SubscriptionPlan::factory()->create()->id,
        'status' => 'active',
        'started_at' => today()->toDateString(),
        'expires_at' => today()->addMonth()->toDateString(),
        'accesses_remaining' => null,
        'accesses_used' => 0,
    ], $subOverrides));

    return $member;
}

// ---------------------------------------------------------------------------
// 1. Happy path
// ---------------------------------------------------------------------------

it('check-in con cert valido e abbonamento attivo restituisce successo', function () {
    $member = memberWithActiveSub();

    $result = $this->service->checkin($member, $this->performer->id);

    expect($result->succeeded())->toBeTrue()
        ->and($result->isDuplicate)->toBeFalse()
        ->and($result->failure)->toBeNull()
        ->and($result->accessLog)->not->toBeNull();

    $this->assertDatabaseHas('access_logs', [
        'member_id' => $member->id,
        'checked_in_by' => $this->performer->id,
    ]);
});

it('check-in incrementa accesses_used e decrementa accesses_remaining', function () {
    $member = memberWithActiveSub(subOverrides: [
        'accesses_remaining' => 5,
        'accesses_used' => 0,
    ]);
    $sub = Subscription::where('member_id', $member->id)->first();

    $this->service->checkin($member, $this->performer->id);

    expect($sub->fresh()->accesses_used)->toBe(1)
        ->and($sub->fresh()->accesses_remaining)->toBe(4);
});

it('check-in con abbonamento illimitato non tocca accesses_remaining null', function () {
    $member = memberWithActiveSub(subOverrides: ['accesses_remaining' => null, 'accesses_used' => 0]);
    $sub = Subscription::where('member_id', $member->id)->first();

    $this->service->checkin($member, $this->performer->id);

    expect($sub->fresh()->accesses_remaining)->toBeNull()
        ->and($sub->fresh()->accesses_used)->toBe(1);
});

// ---------------------------------------------------------------------------
// 2. Certificato medico
// ---------------------------------------------------------------------------

it('check-in fallisce se certificato medico scaduto', function () {
    $member = memberWithActiveSub(memberOverrides: [
        'medical_cert_expiry' => now()->subDay()->toDateString(),
    ]);

    $result = $this->service->checkin($member, $this->performer->id);

    expect($result->succeeded())->toBeFalse()
        ->and($result->failure)->toBe(CheckinFailure::MedicalCertInvalid);

    $this->assertDatabaseMissing('access_logs', ['member_id' => $member->id]);
});

it('check-in fallisce se certificato medico è null', function () {
    $member = memberWithActiveSub(memberOverrides: ['medical_cert_expiry' => null]);

    $result = $this->service->checkin($member, $this->performer->id);

    expect($result->failure)->toBe(CheckinFailure::MedicalCertInvalid);
    $this->assertDatabaseMissing('access_logs', ['member_id' => $member->id]);
});

// ---------------------------------------------------------------------------
// 3. Abbonamento
// ---------------------------------------------------------------------------

it('check-in fallisce se nessun abbonamento attivo', function () {
    $member = Member::factory()->create([
        'medical_cert_expiry' => now()->addYear()->toDateString(),
    ]);

    $result = $this->service->checkin($member, $this->performer->id);

    expect($result->succeeded())->toBeFalse()
        ->and($result->failure)->toBe(CheckinFailure::NoActiveSubscription);
});

it('check-in fallisce se accessi residui a zero', function () {
    $member = memberWithActiveSub(subOverrides: ['accesses_remaining' => 0]);

    $result = $this->service->checkin($member, $this->performer->id);

    expect($result->succeeded())->toBeFalse()
        ->and($result->failure)->toBe(CheckinFailure::NoAccessesLeft);
    $this->assertDatabaseMissing('access_logs', ['member_id' => $member->id]);
});

// ---------------------------------------------------------------------------
// 4. Transazione — accesses_remaining non scende sotto zero
// ---------------------------------------------------------------------------

it('con accesses_remaining = 1 due chiamate sequenziali producono un solo log', function () {
    $member = memberWithActiveSub(subOverrides: [
        'accesses_remaining' => 1,
        'accesses_used' => 0,
    ]);
    $sub = Subscription::where('member_id', $member->id)->first();

    $first = $this->service->checkin($member, $this->performer->id);
    $second = $this->service->checkin($member, $this->performer->id);

    expect($first->succeeded())->toBeTrue()
        ->and($second->succeeded())->toBeFalse()
        ->and($second->failure)->toBe(CheckinFailure::NoAccessesLeft)
        ->and($sub->fresh()->accesses_remaining)->toBe(0);

    expect(AccessLog::where('member_id', $member->id)->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// 5. Idempotenza (finestra temporale)
// ---------------------------------------------------------------------------

it('secondo check-in entro finestra restituisce log esistente come duplicato', function () {
    $member = memberWithActiveSub();

    $first = $this->service->checkin($member, $this->performer->id, idempotencyWindowMinutes: 5);
    $second = $this->service->checkin($member, $this->performer->id, idempotencyWindowMinutes: 5);

    expect($first->succeeded())->toBeTrue()
        ->and($first->isDuplicate)->toBeFalse()
        ->and($second->succeeded())->toBeTrue()
        ->and($second->isDuplicate)->toBeTrue()
        ->and($second->accessLog?->id)->toBe($first->accessLog?->id);

    expect(AccessLog::where('member_id', $member->id)->count())->toBe(1);
});

it('duplicato non decrementa accesses_remaining una seconda volta', function () {
    $member = memberWithActiveSub(subOverrides: ['accesses_remaining' => 3, 'accesses_used' => 0]);
    $sub = Subscription::where('member_id', $member->id)->first();

    $this->service->checkin($member, $this->performer->id, idempotencyWindowMinutes: 5);
    $this->service->checkin($member, $this->performer->id, idempotencyWindowMinutes: 5);

    expect($sub->fresh()->accesses_remaining)->toBe(2)
        ->and($sub->fresh()->accesses_used)->toBe(1);
});

it('check-in fuori finestra di idempotenza crea nuovo log', function () {
    $member = memberWithActiveSub();

    AccessLog::create([
        'member_id' => $member->id,
        'subscription_id' => Subscription::where('member_id', $member->id)->first()->id,
        'checked_in_at' => now()->subMinutes(10),
        'checked_in_by' => $this->performer->id,
    ]);

    $result = $this->service->checkin($member, $this->performer->id, idempotencyWindowMinutes: 5);

    expect($result->succeeded())->toBeTrue()
        ->and($result->isDuplicate)->toBeFalse();

    expect(AccessLog::where('member_id', $member->id)->count())->toBe(2);
});

it('finestra null non applica idempotenza', function () {
    $member = memberWithActiveSub();

    $this->service->checkin($member, $this->performer->id, idempotencyWindowMinutes: null);
    $second = $this->service->checkin($member, $this->performer->id, idempotencyWindowMinutes: null);

    expect($second->isDuplicate)->toBeFalse()
        ->and($second->succeeded())->toBeTrue();

    expect(AccessLog::where('member_id', $member->id)->count())->toBe(2);
});
