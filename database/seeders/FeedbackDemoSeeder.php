<?php

namespace Database\Seeders;

use App\Models\FeedbackSubmission;
use App\Models\User;
use Illuminate\Database\Seeder;

class FeedbackDemoSeeder extends Seeder
{
    public function run(): void
    {
        $atleta = User::where('email', 'atleta@atleta.atleta')->first();

        $entries = [
            [
                'user_id'    => $atleta?->id,
                'page_url'   => '/athlete/session/12',
                'type'       => 'bug',
                'body'       => 'Il bottone "Completa set" non risponde al primo tap su mobile. Devo premere due volte per registrare il set.',
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
                'created_at' => now()->subDays(1),
            ],
            [
                'user_id'    => $atleta?->id,
                'page_url'   => '/athlete/dashboard',
                'type'       => 'suggestion',
                'body'       => 'Sarebbe utile vedere direttamente nella dashboard il peso usato nell\'ultima sessione per ogni esercizio, senza dover aprire il dettaglio.',
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
                'created_at' => now()->subDays(3),
            ],
            [
                'user_id'    => $atleta?->id,
                'page_url'   => '/athlete/session/10/recap',
                'type'       => 'confused',
                'body'       => 'Non capisco cosa significa "tonnellaggio" nel riepilogo sessione. C\'è un modo per avere una spiegazione in-app?',
                'user_agent' => 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Mobile Safari/537.36',
                'created_at' => now()->subDays(5),
            ],
            [
                'user_id'    => null,
                'page_url'   => '/athlete/exercises',
                'type'       => 'suggestion',
                'body'       => 'Aggiungere la possibilità di filtrare gli esercizi per gruppo muscolare direttamente dalla schermata di sostituzione.',
                'user_agent' => 'Mozilla/5.0 (Linux; Android 13; Samsung Galaxy S23) AppleWebKit/537.36 Chrome/122.0 Mobile Safari/537.36',
                'created_at' => now()->subDays(7),
            ],
            [
                'user_id'    => $atleta?->id,
                'page_url'   => '/athlete/bookings',
                'type'       => 'bug',
                'body'       => 'Ho provato a prenotare il corso di martedì ma il bottone "Prenota" rimane disabilitato anche se ci sono posti disponibili.',
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 Safari/604.1',
                'internal_notes' => 'Verificare: potrebbe essere legato alla finestra di prenotazione (booking_opens_days). Da indagare.',
                'created_at' => now()->subDays(10),
            ],
            [
                'user_id'    => null,
                'page_url'   => '/athlete/profile',
                'type'       => 'suggestion',
                'body'       => 'Vorrei poter scaricare un PDF con il riepilogo delle mie misurazioni degli ultimi 3 mesi.',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/124.0',
                'created_at' => now()->subDays(14),
            ],
            [
                'user_id'    => $atleta?->id,
                'page_url'   => '/athlete/session/8',
                'type'       => 'suggestion',
                'body'       => 'Ottima la funzione di sostituzione esercizi! Però sarebbe meglio se i candidati mostrassero anche una miniatura o l\'immagine dell\'esercizio.',
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Safari/604.1',
                'created_at' => now()->subDays(18),
            ],
            [
                'user_id'    => null,
                'page_url'   => '/athlete/notifications',
                'type'       => 'confused',
                'body'       => 'Ho ricevuto una notifica di promemoria per un corso a cui non mi ero iscritto. Non so come sia successo.',
                'user_agent' => 'Mozilla/5.0 (Linux; Android 14; OnePlus 12) AppleWebKit/537.36 Chrome/123.0 Mobile Safari/537.36',
                'internal_notes' => 'Da verificare con ClassReminderNotification — possibile invio errato.',
                'created_at' => now()->subDays(21),
            ],
        ];

        foreach ($entries as $entry) {
            FeedbackSubmission::firstOrCreate(
                [
                    'user_id'    => $entry['user_id'],
                    'page_url'   => $entry['page_url'],
                    'type'       => $entry['type'],
                    'created_at' => $entry['created_at'],
                ],
                [
                    'body'           => $entry['body'],
                    'user_agent'     => $entry['user_agent'] ?? null,
                    'internal_notes' => $entry['internal_notes'] ?? null,
                ]
            );
        }

        $this->command->info('FeedbackDemoSeeder: ' . count($entries) . ' feedback inseriti (idempotente).');
    }
}
