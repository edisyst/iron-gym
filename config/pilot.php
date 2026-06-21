<?php

return [
    'manager_email' => env('PILOT_MANAGER_EMAIL', 'gestore@iron-gym.test'),
    'manager_password' => env('PILOT_MANAGER_PASSWORD', 'changeme'),
    'plans' => [
        ['name' => 'Mensile',     'duration_days' => 30,  'price_cents' => 5000,  'max_accesses' => null],
        ['name' => 'Trimestrale', 'duration_days' => 90,  'price_cents' => 13000, 'max_accesses' => null],
        ['name' => 'Annuale',     'duration_days' => 365, 'price_cents' => 45000, 'max_accesses' => null],
        ['name' => '10 ingressi', 'duration_days' => 180, 'price_cents' => 8000,  'max_accesses' => 10],
    ],
];
