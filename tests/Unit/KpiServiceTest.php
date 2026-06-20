<?php

use App\Models\Member;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\KpiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makePlan(int $priceCents = 10000): SubscriptionPlan
{
    return SubscriptionPlan::create([
        'name' => 'Test Plan',
        'price_cents' => $priceCents,
        'duration_days' => 30,
        'is_active' => true,
    ]);
}

function makeMember(): Member
{
    return Member::factory()->create();
}

function makeSubscription(Member $member, SubscriptionPlan $plan, string $startedAt, string $expiresAt, string $status = 'active'): Subscription
{
    return Subscription::create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'started_at' => $startedAt,
        'expires_at' => $expiresAt,
        'status' => $status,
    ]);
}

it('il fatturato del periodo somma solo le subscription del periodo', function () {
    $plan = makePlan(15000);
    $m1 = makeMember();
    $m2 = makeMember();
    $m3 = makeMember();

    // Dentro il periodo
    makeSubscription($m1, $plan, '2025-01-05', '2025-02-05');
    makeSubscription($m2, $plan, '2025-01-20', '2025-02-20');
    // Fuori dal periodo
    makeSubscription($m3, $plan, '2024-12-01', '2025-01-01');

    $kpi = new KpiService;
    $result = $kpi->revenueByPeriod(Carbon::parse('2025-01-01'), Carbon::parse('2025-01-31'));

    expect(array_sum($result))->toBe(30000); // 2 × 15000
});

it('la retention rate è 100% se tutti gli iscritti hanno rinnovato', function () {
    $plan = makePlan(10000);
    $m1 = makeMember();
    $m2 = makeMember();

    // Attivi a inizio periodo (2025-01-01)
    makeSubscription($m1, $plan, '2024-12-01', '2025-01-31');
    makeSubscription($m2, $plan, '2024-12-01', '2025-01-31');

    // Ancora attivi a fine periodo (2025-01-31)
    // Le stesse subscription coprono anche la data di fine

    $kpi = new KpiService;
    $rate = $kpi->retentionRate(Carbon::parse('2025-01-01'), Carbon::parse('2025-01-31'));

    expect($rate)->toBe(100.0);
});

it('la churn rate è 0% se tutti gli abbonamenti scaduti sono stati rinnovati entro 30 giorni', function () {
    $plan = makePlan(10000);
    $m1 = makeMember();
    $m2 = makeMember();

    // Scadono nel periodo
    makeSubscription($m1, $plan, '2025-01-01', '2025-01-15', 'expired');
    makeSubscription($m2, $plan, '2025-01-01', '2025-01-20', 'expired');

    // Rinnovati entro 30 giorni
    makeSubscription($m1, $plan, '2025-01-16', '2025-02-16');
    makeSubscription($m2, $plan, '2025-01-21', '2025-02-21');

    $kpi = new KpiService;
    $rate = $kpi->churnRate(Carbon::parse('2025-01-01'), Carbon::parse('2025-01-31'));

    expect($rate)->toBe(0.0);
});

it('la churn rate è 100% se nessun abbonamento scaduto è stato rinnovato', function () {
    $plan = makePlan(10000);
    $m1 = makeMember();
    $m2 = makeMember();

    // Scadono nel periodo, nessun rinnovo
    makeSubscription($m1, $plan, '2025-01-01', '2025-01-15', 'expired');
    makeSubscription($m2, $plan, '2025-01-01', '2025-01-20', 'expired');

    $kpi = new KpiService;
    $rate = $kpi->churnRate(Carbon::parse('2025-01-01'), Carbon::parse('2025-01-31'));

    expect($rate)->toBe(100.0);
});
