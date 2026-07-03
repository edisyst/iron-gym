<?php

namespace App\ValueObjects;

/**
 * Proposta di modulazione carichi generata dal ReadinessEvaluator.
 * outcome: 'none' | 'reduce_5pct' | 'reduce_10pct'
 */
readonly class ReadinessProposal
{
    public function __construct(
        public int $score,
        public string $outcome,
        public string $suggestion,
        public bool $includesJointAlert,
    ) {}

    public function requiresModulation(): bool
    {
        return $this->outcome !== 'none';
    }
}
