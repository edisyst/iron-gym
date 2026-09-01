<?php

return [
    'beta_trainers' => array_filter(explode(',', env('FEATURE_BETA_TRAINERS', ''))),
    'group_classes_enabled' => env('FEATURE_GROUP_CLASSES', false),
    'in_app_feedback_enabled' => env('FEATURE_IN_APP_FEEDBACK', false),

    /*
     * Metadati dei flag gestibili da UI (FeatureFlagManager).
     * Fonte unica per label, descrizione, platea, chiave settings, default e gruppo.
     * I gruppi sono: Moduli, Sessione atleta, Sistema.
     */
    'managed_flags' => [

        // ----------------------------------------------------------------
        // Moduli — funzionalita' che la palestra puo' abilitare/disabilitare
        // ----------------------------------------------------------------

        'group_classes' => [
            'group' => 'Moduli',
            'label' => 'Corsi collettivi',
            'description' => 'Modulo prenotazione corsi collettivi (atleta + backoffice).',
            'platea' => 'Tutta la palestra (flag globale)',
            'settings_key' => 'group_classes_enabled',
            'default' => false,
        ],
        'messaging' => [
            'group' => 'Moduli',
            'label' => 'Messaggistica',
            'description' => 'Thread messaggi trainer-atleta (backoffice e app atleta).',
            'platea' => 'Tutta la palestra (flag globale)',
            'settings_key' => 'messaging_enabled',
            'default' => true,
        ],
        'pt_bookings' => [
            'group' => 'Moduli',
            'label' => 'Prenotazioni PT',
            'description' => 'Prenotazione sessioni PT lato atleta. Il calendario backoffice resta sempre visibile.',
            'platea' => 'Atleti (flag globale)',
            'settings_key' => 'pt_bookings_enabled',
            'default' => true,
        ],

        // ----------------------------------------------------------------
        // Sessione atleta — funzioni nel flusso di allenamento
        // ----------------------------------------------------------------

        'readiness_check' => [
            'group' => 'Sessione atleta',
            'label' => 'Check pre-sessione',
            'description' => 'Quattro domande (sonno, stress, dolori, articolazioni) prima di avviare la sessione.',
            'platea' => 'Atleti (flag globale)',
            'settings_key' => 'readiness_check_enabled',
            'default' => true,
        ],
        'exercise_substitution' => [
            'group' => 'Sessione atleta',
            'label' => 'Sostituzione esercizio',
            'description' => 'Permette all\'atleta di sostituire un esercizio durante la sessione.',
            'platea' => 'Atleti (flag globale)',
            'settings_key' => 'exercise_substitution_enabled',
            'default' => true,
        ],
        'session_recap' => [
            'group' => 'Sessione atleta',
            'label' => 'Riepilogo sessione',
            'description' => 'Schermata recap con tonnellaggio, PR e top muscoli dopo la sessione.',
            'platea' => 'Atleti (flag globale)',
            'settings_key' => 'session_recap_enabled',
            'default' => true,
        ],
        'personal_records' => [
            'group' => 'Sessione atleta',
            'label' => 'Record personali',
            'description' => 'Rilevamento PR durante la sessione e pagina storico record.',
            'platea' => 'Atleti (flag globale)',
            'settings_key' => 'personal_records_enabled',
            'default' => true,
        ],
        'weekly_volume' => [
            'group' => 'Sessione atleta',
            'label' => 'Volume settimanale',
            'description' => 'Dashboard volume per gruppo muscolare con confronto MEV/MAV/MRV.',
            'platea' => 'Atleti (flag globale)',
            'settings_key' => 'weekly_volume_enabled',
            'default' => true,
        ],
        'plate_calculator' => [
            'group' => 'Sessione atleta',
            'label' => 'Calcolatore dischi',
            'description' => 'Suggerisce la combinazione dischi per il bilanciere (rimosso da UI atleta, riservato a usi futuri).',
            'platea' => 'Atleti (flag globale)',
            'settings_key' => 'plate_calculator_enabled',
            'default' => true,
        ],

        // ----------------------------------------------------------------
        // Sistema — flag operativi e infrastrutturali
        // ----------------------------------------------------------------

        'financial_reports' => [
            'group' => 'Sistema',
            'label' => 'Report finanziari',
            'description' => 'Dashboard KPI e report economici nella sezione Gestore.',
            'platea' => 'Solo gestore',
            'settings_key' => 'financial_reports_enabled',
            'default' => true,
        ],
        'periodization_engine' => [
            'group' => 'Sistema',
            'label' => 'Motore di periodizzazione',
            'description' => 'Progressione automatica set e carichi nei mesocicli.',
            'platea' => 'Gestore + trainer nella lista beta (FEATURE_BETA_TRAINERS)',
            'settings_key' => 'periodization_engine_enabled',
            'default' => true,
        ],
        'push_notifications' => [
            'group' => 'Sistema',
            'label' => 'Notifiche push',
            'description' => 'Registrazione service worker e notifiche web push per la PWA.',
            'platea' => 'Atleti e trainer',
            'settings_key' => 'push_notifications_enabled',
            'default' => false,
        ],
        'outbound_notifications' => [
            'group' => 'Sistema',
            'label' => 'Invii verso l\'esterno',
            'description' => 'Kill switch globale per email transazionali e push schedulati. Spegnerlo blocca tutti i job di invio.',
            'platea' => 'Tutta la palestra (flag globale)',
            'settings_key' => 'outbound_notifications_enabled',
            'default' => true,
        ],
        'in_app_feedback' => [
            'group' => 'Sistema',
            'label' => 'Feedback in-app',
            'description' => 'Widget feedback utenti nel layout backoffice e app atleta.',
            'platea' => 'Tutti gli utenti (flag globale)',
            'settings_key' => 'in_app_feedback_enabled',
            'default' => false,
        ],
        'public_api' => [
            'group' => 'Sistema',
            'label' => 'API pubblica',
            'description' => 'Superficie API HTTP JSON /api/v1 per integrazioni esterne e script interni. Spenta per default; richiede account di servizio con token Sanctum.',
            'platea' => 'Account di servizio (api_client)',
            'settings_key' => 'public_api_enabled',
            'default' => false,
        ],
    ],
];
