<?php

return [
    /*
     * Richieste al minuto per token autenticato (by user ID).
     * Usa il driver Redis (predis).
     */
    'rate_limit_authenticated' => env('API_RATE_LIMIT_AUTH', 60),

    /*
     * Richieste al minuto per IP anonimo (richieste senza token).
     */
    'rate_limit_anonymous' => env('API_RATE_LIMIT_ANON', 10),
];
