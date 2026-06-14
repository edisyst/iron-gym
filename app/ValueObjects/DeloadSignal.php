<?php

namespace App\ValueObjects;

/**
 * Segnale di deload restituito dal DeloadEvaluator.
 */
readonly class DeloadSignal
{
    /**
     * @param  array<string>  $activeTriggers  Lista dei trigger attivi
     * @param  int|null  $suggestedWeekNumber  Numero settimana in cui applicare il deload
     * @param  string|null  $notes  Note descrittive per il trainer
     */
    public function __construct(
        public array $activeTriggers,
        public ?int $suggestedWeekNumber,
        public ?string $notes = null,
    ) {}

    public function isDeloadNeeded(): bool
    {
        return count($this->activeTriggers) > 0;
    }
}
