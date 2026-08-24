<?php

return [
    // Giorni prima dell'occorrenza in cui si apre la prenotazione
    'booking_opens_days' => 7,

    // Minuti prima dell'inizio entro cui la prenotazione si chiude
    'booking_closes_minutes' => 30,

    // Ore prima dell'inizio entro cui la cancellazione è gratuita
    'free_cancel_hours' => 3,

    // Giorni futuri per cui il command genera occorrenze dal palinsesto
    'generation_horizon_days' => 28,
];
