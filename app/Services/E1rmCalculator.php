<?php

namespace App\Services;

/**
 * Calcolo dell'1RM stimato (e1RM) con diverse formule.
 */
class E1rmCalculator
{
    /**
     * Formula di Epley: 1RM = w * (1 + reps/30)
     * Restituisce null se weight o reps sono null, o se reps <= 0.
     */
    public static function epley(?float $weight, ?int $reps): ?float
    {
        if ($weight === null || $reps === null || $reps <= 0) {
            return null;
        }

        return round($weight * (1 + $reps / 30), 2);
    }

    /**
     * Formula di Brzycki: 1RM = w * 36 / (37 - reps)
     */
    public static function brzycki(?float $weight, ?int $reps): ?float
    {
        if ($weight === null || $reps === null || $reps <= 0 || $reps >= 37) {
            return null;
        }

        return round($weight * 36 / (37 - $reps), 2);
    }

    /**
     * Formula di Lombardi: 1RM = w * reps^0.10
     */
    public static function lombardi(?float $weight, ?int $reps): ?float
    {
        if ($weight === null || $reps === null || $reps <= 0) {
            return null;
        }

        return round($weight * ($reps ** 0.10), 2);
    }
}
