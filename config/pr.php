<?php

return [
    /*
    | Soglia massima ripetizioni per il calcolo e1RM con Epley.
    | Oltre questa soglia la formula degrada significativamente.
    */
    'max_reps_epley' => (int) env('PR_MAX_REPS_EPLEY', 12),

    /*
    | Numero minimo di sessioni su un esercizio prima di registrare PR.
    | Evita la pioggia di falsi PR nei primissimi allenamenti.
    */
    'min_sessions_before_pr' => (int) env('PR_MIN_SESSIONS', 3),
];
