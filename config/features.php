<?php

return [
    'beta_trainers' => array_filter(explode(',', env('FEATURE_BETA_TRAINERS', ''))),
    'group_classes_enabled' => env('FEATURE_GROUP_CLASSES', false),
    'in_app_feedback_enabled' => env('FEATURE_IN_APP_FEEDBACK', false),

    /*
     * Metadati dei flag gestibili da UI (FeatureFlagManager).
     * Fonte unica per label, descrizione, platea, chiave settings e default.
     */
    'managed_flags' => [
        'periodization_engine' => [
            'label' => 'Motore di periodizzazione',
            'description' => 'Progressione automatica set e carichi nei mesocicli.',
            'platea' => 'Gestore + trainer nella lista beta (FEATURE_BETA_TRAINERS)',
            'settings_key' => 'periodization_engine_enabled',
            'default' => true,
        ],
        'push_notifications' => [
            'label' => 'Notifiche push',
            'description' => 'Registrazione service worker e notifiche web push per la PWA.',
            'platea' => 'Atleti e trainer',
            'settings_key' => 'push_notifications_enabled',
            'default' => false,
        ],
        'group_classes' => [
            'label' => 'Corsi collettivi',
            'description' => 'Modulo prenotazione corsi collettivi (atleta + backoffice).',
            'platea' => 'Tutta la palestra (flag globale, nessuna restrizione per ruolo)',
            'settings_key' => 'group_classes_enabled',
            'default' => false,
        ],
        'financial_reports' => [
            'label' => 'Report finanziari',
            'description' => 'Dashboard KPI e report economici nella sezione Gestore.',
            'platea' => 'Solo gestore',
            'settings_key' => 'financial_reports_enabled',
            'default' => true,
        ],
    ],
];
