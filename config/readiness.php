<?php

return [
    /*
    | Score soglia alta: score >= high → nessuna modifica carichi.
    | Score range 0-12 (somma di 4 campi 0-3, dove 3 = ottimo).
    */
    'thresholds' => [
        'high' => (int) env('READINESS_THRESHOLD_HIGH', 9),
        'low' => (int) env('READINESS_THRESHOLD_LOW', 5),
    ],

    /*
    | Percentuale di riduzione carichi per fascia:
    | medium: score >= low && < high
    | low:    score < low
    */
    'reduction_pct' => [
        'medium' => (int) env('READINESS_REDUCTION_MEDIUM', 5),
        'low' => (int) env('READINESS_REDUCTION_LOW', 10),
    ],

    /*
    | joint_status <= questa soglia → proposta include invito a segnalare al trainer.
    */
    'joint_alert_threshold' => (int) env('READINESS_JOINT_ALERT', 1),

    /*
    | Numero minimo di set non completati per ridurre di un set (solo fascia low).
    */
    'min_sets_for_removal' => (int) env('READINESS_MIN_SETS_REMOVAL', 3),
];
