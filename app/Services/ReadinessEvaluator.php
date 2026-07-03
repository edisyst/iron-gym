<?php

namespace App\Services;

use App\Models\SessionReadinessCheck;
use App\ValueObjects\ReadinessProposal;

class ReadinessEvaluator
{
    public function evaluate(SessionReadinessCheck $check): ReadinessProposal
    {
        $score = $check->score;

        $highThreshold = (int) config('readiness.thresholds.high', 9);
        $lowThreshold = (int) config('readiness.thresholds.low', 5);

        if ($score >= $highThreshold) {
            $outcome = 'none';
            $suggestion = 'Readiness ottimale — nessuna modifica ai carichi pianificati.';
        } elseif ($score >= $lowThreshold) {
            $pct = (int) config('readiness.reduction_pct.medium', 5);
            $outcome = 'reduce_5pct';
            $suggestion = "Readiness moderata — carichi ridotti del {$pct}%.";
        } else {
            $pct = (int) config('readiness.reduction_pct.low', 10);
            $outcome = 'reduce_10pct';
            $suggestion = "Readiness bassa — carichi ridotti del {$pct}% e un set in meno sugli esercizi più voluminosi.";
        }

        $jointAlertThreshold = (int) config('readiness.joint_alert_threshold', 1);
        $includesJointAlert = $check->joint_status <= $jointAlertThreshold;

        return new ReadinessProposal($score, $outcome, $suggestion, $includesJointAlert);
    }

    /**
     * Calcola il nuovo planned_weight_kg arrotondato a 2.5 kg.
     */
    public function applyReduction(float $weightKg, int $reductionPct): float
    {
        return round($weightKg * (1 - $reductionPct / 100) / 2.5) * 2.5;
    }
}
