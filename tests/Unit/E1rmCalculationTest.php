<?php

use App\Services\E1rmCalculator;

it('epley restituisce il valore corretto per 100kg x 5 reps', function () {
    // 100 * (1 + 5/30) = 100 * 1.16666... = 116.67
    $result = E1rmCalculator::epley(100.0, 5);
    expect($result)->toBe(116.67);
});

it('epley restituisce null se actual_reps è zero', function () {
    expect(E1rmCalculator::epley(100.0, 0))->toBeNull();
});

it('epley restituisce il valore corretto se actual_reps è 1', function () {
    // 100 * (1 + 1/30) = 100 * 1.03333... = 103.33
    $result = E1rmCalculator::epley(100.0, 1);
    expect($result)->toBe(103.33);
});

it('epley restituisce null se weight è null', function () {
    expect(E1rmCalculator::epley(null, 5))->toBeNull();
});

it('epley restituisce null se reps è null', function () {
    expect(E1rmCalculator::epley(100.0, null))->toBeNull();
});
