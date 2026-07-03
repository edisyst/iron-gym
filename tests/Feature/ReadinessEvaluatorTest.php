<?php

use App\Models\SessionReadinessCheck;
use App\Services\ReadinessEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'readiness.thresholds.high' => 9,
        'readiness.thresholds.low' => 5,
        'readiness.reduction_pct.medium' => 5,
        'readiness.reduction_pct.low' => 10,
        'readiness.joint_alert_threshold' => 1,
        'readiness.min_sets_for_removal' => 3,
    ]);
});

function makeCheck(int $sleep, int $stress, int $soreness, int $joint): SessionReadinessCheck
{
    $check = new SessionReadinessCheck;
    $check->sleep_quality = $sleep;
    $check->stress_level = $stress;
    $check->soreness_level = $soreness;
    $check->joint_status = $joint;

    return $check;
}

// ---- Mappatura score → esito ----

it('score >= 9 produce outcome none', function () {
    $check = makeCheck(3, 3, 2, 2); // score 10
    $proposal = app(ReadinessEvaluator::class)->evaluate($check);

    expect($proposal->score)->toBe(10)
        ->and($proposal->outcome)->toBe('none')
        ->and($proposal->requiresModulation())->toBeFalse();
});

it('score esattamente 9 produce outcome none', function () {
    $check = makeCheck(3, 2, 2, 2); // score 9
    $proposal = app(ReadinessEvaluator::class)->evaluate($check);

    expect($proposal->score)->toBe(9)
        ->and($proposal->outcome)->toBe('none');
});

it('score 5-8 produce outcome reduce_5pct', function () {
    $check = makeCheck(1, 2, 2, 1); // score 6
    $proposal = app(ReadinessEvaluator::class)->evaluate($check);

    expect($proposal->score)->toBe(6)
        ->and($proposal->outcome)->toBe('reduce_5pct')
        ->and($proposal->requiresModulation())->toBeTrue();
});

it('score esattamente 5 produce outcome reduce_5pct', function () {
    $check = makeCheck(1, 1, 2, 1); // score 5
    $proposal = app(ReadinessEvaluator::class)->evaluate($check);

    expect($proposal->score)->toBe(5)
        ->and($proposal->outcome)->toBe('reduce_5pct');
});

it('score < 5 produce outcome reduce_10pct', function () {
    $check = makeCheck(0, 1, 1, 1); // score 3
    $proposal = app(ReadinessEvaluator::class)->evaluate($check);

    expect($proposal->score)->toBe(3)
        ->and($proposal->outcome)->toBe('reduce_10pct')
        ->and($proposal->requiresModulation())->toBeTrue();
});

it('score 0 produce outcome reduce_10pct', function () {
    $check = makeCheck(0, 0, 0, 0); // score 0
    $proposal = app(ReadinessEvaluator::class)->evaluate($check);

    expect($proposal->score)->toBe(0)
        ->and($proposal->outcome)->toBe('reduce_10pct');
});

// ---- Arrotondamento carichi ----

it('riduzione 5% su 100kg produce 95kg', function () {
    $evaluator = app(ReadinessEvaluator::class);

    expect($evaluator->applyReduction(100.0, 5))->toBe(95.0);
});

it('riduzione 10% su 80kg arrotonda a 72.5kg', function () {
    $evaluator = app(ReadinessEvaluator::class);

    // 80 * 0.9 = 72 → 72/2.5 = 28.8 → round(28.8)=29 → 29*2.5 = 72.5
    expect($evaluator->applyReduction(80.0, 10))->toBe(72.5);
});

it('riduzione 10% su 75kg produce 67.5kg', function () {
    $evaluator = app(ReadinessEvaluator::class);

    expect($evaluator->applyReduction(75.0, 10))->toBe(67.5);
});

it('arrotonda al multiplo di 2.5 più vicino quando non esatto', function () {
    $evaluator = app(ReadinessEvaluator::class);

    // 70 * 0.9 = 63 → round(63/2.5)*2.5 = round(25.2)*2.5 = 25*2.5 = 62.5
    expect($evaluator->applyReduction(70.0, 10))->toBe(62.5);
});

// ---- Allerta articolazioni ----

it('joint_status 0 attiva includesJointAlert', function () {
    $check = makeCheck(2, 2, 2, 0);
    $proposal = app(ReadinessEvaluator::class)->evaluate($check);

    expect($proposal->includesJointAlert)->toBeTrue();
});

it('joint_status 1 attiva includesJointAlert', function () {
    $check = makeCheck(2, 2, 2, 1);
    $proposal = app(ReadinessEvaluator::class)->evaluate($check);

    expect($proposal->includesJointAlert)->toBeTrue();
});

it('joint_status 2 non attiva includesJointAlert', function () {
    $check = makeCheck(2, 2, 2, 2);
    $proposal = app(ReadinessEvaluator::class)->evaluate($check);

    expect($proposal->includesJointAlert)->toBeFalse();
});

// ---- Score accessor ----

it('SessionReadinessCheck calcola score correttamente', function () {
    $check = makeCheck(2, 1, 3, 0);

    expect($check->score)->toBe(6);
});
