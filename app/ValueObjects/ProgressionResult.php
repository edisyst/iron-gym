<?php

namespace App\ValueObjects;

/**
 * Risultato dell'applicazione di una progressione settimanale.
 */
readonly class ProgressionResult
{
    /**
     * @param  array<string, int>  $setsAddedByMuscle  Slug muscolo => set aggiunti (positivi, negativi o 0)
     * @param  array<string>  $feedbackTriggers  Metriche feedback che hanno influenzato la decisione
     * @param  string  $action  'progressed' | 'held' | 'reduced' | 'deload'
     * @param  string|null  $note  Note aggiuntive per il trainer
     */
    public function __construct(
        public array $setsAddedByMuscle,
        public array $feedbackTriggers,
        public string $action,
        public ?string $note = null,
    ) {}
}
