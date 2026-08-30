<?php

namespace Database\Seeders;

use App\Models\ClassBooking;
use App\Models\ClassOccurrence;
use App\Models\Exercise;
use App\Models\ExerciseSet;
use App\Models\GroupClass;
use App\Models\Member;
use App\Models\Message;
use App\Models\PersonalRecord;
use App\Models\PtBooking;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Dati scenario per test manuali.
 * Fonde: R09R31DemoSeeder + FunctionalTestSeeder (senza scenarioOrariApertura,
 * gia' coperto da OpeningHoursSeeder nel gruppo base).
 * Idempotente: controlla esistenza prima di inserire.
 */
class ScenarioDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('ScenarioDemoSeeder: vietato in production.');

            return;
        }

        $athlete = User::where('email', 'atleta@atleta.atleta')->first();
        $trainer1 = User::where('email', 'trainer@trainer.trainer')->first();
        $trainer2 = User::where('email', 'trainer2@trainer.trainer')->first();

        if (! $athlete || ! $trainer1) {
            $this->command->warn('Utenti demo non trovati. Esegui prima CoreDemoSeeder.');

            return;
        }

        $member = Member::where('user_id', $athlete->id)->first();

        // Sezione R09R31
        $this->seedPersonalRecords($athlete);
        $this->seedMessages($athlete, $trainer1, $trainer2);
        $this->seedSuspendedSubscription();
        $this->seedMembersWithNotes();
        $this->seedNotifications($athlete, $member);
        $this->seedPendingPtBooking($member, $trainer1);
        $this->seedExpiredSubscriptionAthlete();

        // Scenari test funzionali
        $this->scenarioCorsiCollettivi();
        $this->scenarioNotifiche();
        $this->scenarioCheckIn();
        $this->scenarioAbbonamenti();

        $this->command->info('ScenarioDemoSeeder: PR, messaggi, sospensione, note, notifiche, PT pending, scenari test completati.');
    }

    // =========================================================================
    // Sezione R09R31
    // =========================================================================

    /**
     * PT in attesa di conferma per l'atleta demo principale: serve a provare
     * l'annullamento dalla dashboard atleta (R14), che agisce su pending e
     * confirmed. Senza questo l'atleta demo non aveva prenotazioni pending.
     */
    private function seedPendingPtBooking(?Member $member, User $trainer): void
    {
        if ($member === null) {
            return;
        }

        $exists = PtBooking::where('member_id', $member->id)
            ->where('status', 'pending')
            ->whereDate('booked_date', '>=', today())
            ->exists();

        if ($exists) {
            return;
        }

        $date = today()->addDays(3);

        PtBooking::create([
            'trainer_id' => $trainer->id,
            'member_id' => $member->id,
            'booked_date' => $date->toDateString(),
            'start_time' => '17:00:00',
            'end_time' => '18:00:00',
            'status' => 'pending',
            'cancellation_deadline' => $date->copy()->setTime(17, 0)->subDay(),
            'notes' => 'Richiesta valutazione tecnica su squat.',
        ]);
    }

    /**
     * Atleta demo con abbonamento scaduto e certificato medico scaduto:
     * copre il badge "Scaduto" nel profilo (R13) e il blocco dei prerequisiti
     * di iscrizione ai corsi (R09 Step 2).
     *
     * Lo status resta 'active' perche' e' cosi' che il profilo distingue un
     * abbonamento lapsed da uno assente: filtra su status='active' e calcola
     * il badge da expires_at.
     */
    private function seedExpiredSubscriptionAthlete(): void
    {
        $member = Member::where('email', 'alessia.colombo@example.com')->first();

        if ($member === null) {
            return;
        }

        $member->subscriptions()
            ->orderByDesc('expires_at')
            ->first()
            ?->update([
                'status' => 'active',
                'started_at' => today()->subDays(40),
                'expires_at' => today()->subDays(5),
            ]);

        $member->update(['medical_cert_expiry' => today()->subDays(7)]);
    }

    private function seedPersonalRecords(User $athlete): void
    {
        if (PersonalRecord::where('athlete_id', $athlete->id)->exists()) {
            return;
        }

        $exercises = [
            'barbell_bench_press' => ['label' => 'Panca piana', 'values' => [95.0, 100.0, 102.5, 105.0, 107.5]],
            'conventional_deadlift' => ['label' => 'Stacco da terra', 'values' => [150.0, 155.0, 160.0, 162.5, 165.0]],
            'back_squat_high_bar' => ['label' => 'Squat', 'values' => [120.0, 125.0, 127.5, 130.0, 132.5]],
            'overhead_press_standing' => ['label' => 'Lento avanti', 'values' => [60.0, 62.5, 65.0, 65.0, 67.5]],
            'barbell_curl' => ['label' => 'Curl bilanciere', 'values' => [40.0, 42.5, 42.5, 45.0, 47.5]],
        ];

        foreach ($exercises as $slug => $data) {
            $exercise = Exercise::where('slug', $slug)->first();

            if (! $exercise) {
                continue;
            }

            // Trova un ExerciseSet reale dell'atleta per questo esercizio (richiesto NOT NULL)
            $exerciseSet = ExerciseSet::whereHas('sessionExercise.session.week.mesocycle', function ($q) use ($athlete) {
                $q->where('athlete_id', $athlete->id);
            })->whereHas('sessionExercise', fn ($q) => $q->where('exercise_id', $exercise->id))
                ->where('is_warmup', 0)
                ->orderByDesc('id')
                ->first();

            if (! $exerciseSet) {
                continue;
            }

            foreach ($data['values'] as $i => $value) {
                $daysAgo = (count($data['values']) - $i - 1) * 14 + random_int(0, 5);
                PersonalRecord::create([
                    'athlete_id' => $athlete->id,
                    'exercise_id' => $exercise->id,
                    'exercise_set_id' => $exerciseSet->id,
                    'record_type' => 'e1rm',
                    'value' => $value,
                    'achieved_at' => now()->subDays($daysAgo),
                ]);
            }
        }
    }

    private function seedMessages(User $athlete, User $trainer1, ?User $trainer2): void
    {
        if (Message::where('sender_id', $trainer1->id)->where('receiver_id', $athlete->id)->exists()) {
            return;
        }

        $thread = [
            [$trainer1, $athlete, 'Ciao! Come sono andati gli allenamenti questa settimana?', now()->subDays(10)],
            [$athlete, $trainer1, 'Bene, la panca e andata molto bene — ho fatto 3x8 a 100kg.', now()->subDays(10)->addHours(2)],
            [$trainer1, $athlete, 'Ottimo! La settimana prossima proviamo ad aumentare a 102.5kg.', now()->subDays(10)->addHours(3)],
            [$athlete, $trainer1, 'Perfetto, ci vediamo giovedi allora.', now()->subDays(10)->addHours(4)],

            [$trainer1, $athlete, 'Ricordati di fare lo stretching post-sessione, soprattutto per il petto.', now()->subDays(7)],
            [$athlete, $trainer1, 'Lo faccio regolarmente, grazie per il reminder.', now()->subDays(7)->addHours(1)],

            [$trainer1, $athlete, 'Come ti senti oggi? Domani abbiamo sessione PT.', now()->subDays(2)],
            [$athlete, $trainer1, 'Benissimo, pronto per allenare duro!', now()->subDays(2)->addHours(3)],
            [$trainer1, $athlete, 'Perfetto. Ci vediamo alle 9:00. Porta scarpe da ginnastica.', now()->subDays(1)],
        ];

        foreach ($thread as [$sender, $receiver, $body, $date]) {
            Message::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'body' => $body,
                'read_at' => $date->lt(now()->subDays(3)) ? $date->addMinutes(10) : null,
                'created_at' => $date,
            ]);
        }

        if ($trainer2) {
            $thread2 = [
                [$trainer2, $athlete, 'Salve! Sono Elena, il tuo trainer per i corsi collettivi. Ti aspetto allo Spinning di domani!', now()->subDays(3)],
                [$athlete, $trainer2, 'Grazie! Non vedo l\'ora.', now()->subDays(3)->addHours(1)],
                [$trainer2, $athlete, 'Porta una borraccia d\'acqua — il corso e intenso!', now()->subDays(1)->addHours(2)],
            ];

            foreach ($thread2 as [$sender, $receiver, $body, $date]) {
                Message::create([
                    'sender_id' => $sender->id,
                    'receiver_id' => $receiver->id,
                    'body' => $body,
                    'read_at' => null,
                    'created_at' => $date,
                ]);
            }
        }
    }

    private function seedSuspendedSubscription(): void
    {
        if (Subscription::where('status', 'suspended')->exists()) {
            return;
        }

        $member = Member::whereHas('subscriptions', fn ($q) => $q->where('status', 'active'))
            ->orderByDesc('id')
            ->skip(2)
            ->first();

        if (! $member) {
            return;
        }

        $sub = $member->subscriptions()->where('status', 'active')->first();

        if ($sub) {
            $sub->update(['status' => 'suspended']);
        }
    }

    private function seedMembersWithNotes(): void
    {
        $membersWithNotes = [
            ['email' => 'giovanni.ferrari@example.com', 'notes' => 'Problema alla spalla destra — evitare esercizi overhead. Medico sportivo dottor Bianchi.'],
            ['email' => 'marco.ricci@example.com', 'notes' => 'Preferisce orari mattutini. Ha chiesto di essere contattato per rinnovo abbonamento.'],
        ];

        foreach ($membersWithNotes as $data) {
            Member::where('email', $data['email'])
                ->whereNull('notes')
                ->update(['notes' => $data['notes']]);
        }
    }

    private function seedNotifications(User $athlete, ?Member $member): void
    {
        if (DB::table('notifications')->where('notifiable_id', $athlete->id)->exists()) {
            return;
        }

        $occurrence = ClassOccurrence::where('status', 'planned')
            ->whereDate('date', '>', today())
            ->first();

        $notifications = [];

        // Notifica promemoria corso collettivo (R11)
        if ($occurrence && $member) {
            $className = $occurrence->groupClass->name ?? 'Functional Training';
            $classTime = substr($occurrence->start_time, 0, 5);
            $notifications[] = [
                'id' => Str::uuid()->toString(),
                'type' => 'App\\Notifications\\ClassReminderNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $athlete->id,
                'data' => json_encode([
                    'type' => 'class_reminder',
                    'occurrence_id' => $occurrence->id,
                    'message' => "Domani hai {$className} alle {$classTime}.",
                ]),
                'read_at' => null,
                'created_at' => now()->subHours(14),
                'updated_at' => now()->subHours(14),
            ];
        }

        // Notifica scadenza abbonamento (R10 centro notifiche)
        $notifications[] = [
            'id' => Str::uuid()->toString(),
            'type' => 'App\\Notifications\\SubscriptionExpiryNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $athlete->id,
            'data' => json_encode([
                'type' => 'subscription_expiry',
                'message' => 'Il tuo abbonamento scade tra 20 giorni. Rinnova ora.',
            ]),
            'read_at' => now()->subHours(2),
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ];

        // Notifica nuovo messaggio — letta
        $notifications[] = [
            'id' => Str::uuid()->toString(),
            'type' => 'App\\Notifications\\NewMessageNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $athlete->id,
            'data' => json_encode([
                'type' => 'new_message',
                'message' => 'Hai un nuovo messaggio da Luca Bianchi.',
                'sender_name' => 'Luca Bianchi',
            ]),
            'read_at' => now()->subDays(5),
            'created_at' => now()->subDays(7),
            'updated_at' => now()->subDays(7),
        ];

        // Notifica cancellazione corso (R09 Step 4)
        if ($member) {
            $notifications[] = [
                'id' => Str::uuid()->toString(),
                'type' => 'App\\Notifications\\ClassOccurrenceCancelledNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $athlete->id,
                'data' => json_encode([
                    'type' => 'class_cancelled',
                    'message' => 'Il corso Yoga del 15/08 e stato cancellato.',
                ]),
                'read_at' => now()->subDays(3),
                'created_at' => now()->subDays(9),
                'updated_at' => now()->subDays(9),
            ];
        }

        DB::table('notifications')->insert($notifications);
    }

    // =========================================================================
    // Scenari test funzionali (ex FunctionalTestSeeder)
    // scenarioOrariApertura rimosso: gia' coperto da OpeningHoursSeeder.
    // =========================================================================

    /**
     * Crea ClassOccurrence e ClassBooking per i test manuali delle aree CLS, NOT, PRF-tab-corsi.
     *
     * Occorrenze materializzate:
     * A  Yoga Full now+3 10:00 capacity=3 — 3 confirmed + federica waitlisted (TC-CLS-009/012)
     * B  Happy path now+2 18:00 capacity=10 — nessun booking (TC-CLS-001)
     * C  Overlap target now+2 18:30 capacity=10 — nessun booking (TC-CLS-007)
     * D  Finestra non aperta now+8 10:00 (TC-CLS-005)
     * E  Vuota per eliminazione now+6 10:00 (TC-CLS-014)
     * F  Cancellazione con partecipanti now+4 11:00 — atleta+giovanni confirmed (TC-CLS-013)
     * G  Domani 09:00 — atleta confirmed (TC-NOT-005)
     * H  Passata non completata now-2 09:00 — atleta+giovanni confirmed (TC-CLS-015/016)
     * I  Gia completata now-5 07:00 status=completed (TC-CLS-017)
     * J  Storico profilo atleta: completed now-10 con attended_at (TC-PRF-008)
     * K  Storico profilo atleta: cancelled_by_athlete now-7 (TC-PRF-008)
     * L  Trainer overlap now+5 14:00 + PtBooking trainer1 stesso slot (REG-003)
     * M  GroupClass senza occorrenze future: slug meditazione-attiva (TC-CAT-003)
     */
    private function scenarioCorsiCollettivi(): void
    {
        $trainer1 = User::where('email', 'trainer@trainer.trainer')->first();

        if (! $trainer1) {
            $this->command->warn('CLS: trainer@trainer.trainer non trovato — skip.');

            return;
        }

        $trainer2 = User::where('email', 'trainer2@trainer.trainer')->first() ?? $trainer1;

        $yogaFlow = GroupClass::where('slug', 'yoga-flow')->first();
        $functionalTraining = GroupClass::where('slug', 'functional-training')->first();
        $calisthenics = GroupClass::where('slug', 'calisthenics')->first();
        $pilates = GroupClass::where('slug', 'pilates')->first();
        $fallback = $yogaFlow ?? $functionalTraining ?? GroupClass::first();

        if (! $fallback) {
            $this->command->warn('CLS: nessuna GroupClass trovata — esegui ClassDemoSeeder prima.');

            return;
        }

        $gcYoga = $yogaFlow ?? $fallback;
        $gcFunctional = $functionalTraining ?? $fallback;
        $gcCali = $calisthenics ?? $fallback;
        $gcPilates = $pilates ?? $fallback;

        $mAtleta = Member::where('email', 'atleta@atleta.atleta')->first();
        $mGiovanni = Member::where('email', 'giovanni.ferrari@example.com')->first();
        $mMarco = Member::where('email', 'marco.ricci@example.com')->first();
        $mFederica = Member::where('email', 'federica.esposito@example.com')->first();

        // --- A: Yoga Full (TC-CLS-009/012) ---
        $occYogaFull = ClassOccurrence::firstOrCreate(
            [
                'group_class_id' => $gcYoga->id,
                'date' => Carbon::now()->addDays(3)->toDateString(),
                'start_time' => '10:00:00',
            ],
            [
                'class_schedule_id' => null,
                'end_time' => '11:00:00',
                'trainer_id' => $trainer2->id,
                'capacity' => 3,
                'status' => 'planned',
            ]
        );

        foreach ([$mAtleta, $mGiovanni, $mMarco] as $member) {
            if ($member) {
                ClassBooking::firstOrCreate(
                    ['class_occurrence_id' => $occYogaFull->id, 'member_id' => $member->id],
                    ['status' => 'confirmed', 'position' => null]
                );
            }
        }

        if ($mFederica) {
            ClassBooking::firstOrCreate(
                ['class_occurrence_id' => $occYogaFull->id, 'member_id' => $mFederica->id],
                ['status' => 'waitlisted', 'position' => 1]
            );
        }

        // --- B: Happy path (TC-CLS-001) — posti liberi, nessun booking pre-esistente per atleta ---
        ClassOccurrence::firstOrCreate(
            [
                'group_class_id' => $gcFunctional->id,
                'date' => Carbon::now()->addDays(2)->toDateString(),
                'start_time' => '18:00:00',
            ],
            [
                'class_schedule_id' => null,
                'end_time' => '19:00:00',
                'trainer_id' => $trainer1->id,
                'capacity' => 10,
                'status' => 'planned',
            ]
        );

        // --- C: Overlap target (TC-CLS-007) — stesso giorno di B, orario sovrapposto 18:30-19:30 ---
        ClassOccurrence::firstOrCreate(
            [
                'group_class_id' => $gcYoga->id,
                'date' => Carbon::now()->addDays(2)->toDateString(),
                'start_time' => '18:30:00',
            ],
            [
                'class_schedule_id' => null,
                'end_time' => '19:30:00',
                'trainer_id' => $trainer2->id,
                'capacity' => 10,
                'status' => 'planned',
            ]
        );

        // --- D: Finestra non aperta (TC-CLS-005) ---
        ClassOccurrence::firstOrCreate(
            [
                'group_class_id' => $gcCali->id,
                'date' => Carbon::now()->addDays(8)->toDateString(),
                'start_time' => '10:00:00',
            ],
            [
                'class_schedule_id' => null,
                'end_time' => '11:15:00',
                'trainer_id' => $trainer1->id,
                'capacity' => 10,
                'status' => 'planned',
            ]
        );

        // --- E: Vuota per eliminazione senza partecipanti (TC-CLS-014) ---
        ClassOccurrence::firstOrCreate(
            [
                'group_class_id' => $gcCali->id,
                'date' => Carbon::now()->addDays(6)->toDateString(),
                'start_time' => '10:00:00',
            ],
            [
                'class_schedule_id' => null,
                'end_time' => '11:15:00',
                'trainer_id' => $trainer1->id,
                'capacity' => 10,
                'status' => 'planned',
            ]
        );

        // --- F: Cancellazione con partecipanti (TC-CLS-013) ---
        $occCancTest = ClassOccurrence::firstOrCreate(
            [
                'group_class_id' => $gcPilates->id,
                'date' => Carbon::now()->addDays(4)->toDateString(),
                'start_time' => '11:00:00',
            ],
            [
                'class_schedule_id' => null,
                'end_time' => '12:00:00',
                'trainer_id' => $trainer1->id,
                'capacity' => 10,
                'status' => 'planned',
            ]
        );

        foreach ([$mAtleta, $mGiovanni] as $member) {
            if ($member) {
                ClassBooking::firstOrCreate(
                    ['class_occurrence_id' => $occCancTest->id, 'member_id' => $member->id],
                    ['status' => 'confirmed', 'position' => null]
                );
            }
        }

        // --- G: Domani con atleta confirmed (TC-NOT-005: send-reminders) ---
        $occTomorrow = ClassOccurrence::firstOrCreate(
            [
                'group_class_id' => $gcYoga->id,
                'date' => Carbon::now()->addDay()->toDateString(),
                'start_time' => '09:00:00',
            ],
            [
                'class_schedule_id' => null,
                'end_time' => '10:00:00',
                'trainer_id' => $trainer2->id,
                'capacity' => 10,
                'status' => 'planned',
            ]
        );

        if ($mAtleta) {
            ClassBooking::firstOrCreate(
                ['class_occurrence_id' => $occTomorrow->id, 'member_id' => $mAtleta->id],
                ['status' => 'confirmed', 'position' => null]
            );
        }

        // --- H: Passata non completata (TC-CLS-015/016) ---
        $occPast = ClassOccurrence::firstOrCreate(
            [
                'group_class_id' => $gcFunctional->id,
                'date' => Carbon::now()->subDays(2)->toDateString(),
                'start_time' => '09:00:00',
            ],
            [
                'class_schedule_id' => null,
                'end_time' => '10:00:00',
                'trainer_id' => $trainer1->id,
                'capacity' => 10,
                'status' => 'planned',
            ]
        );

        foreach ([$mAtleta, $mGiovanni] as $member) {
            if ($member) {
                ClassBooking::firstOrCreate(
                    ['class_occurrence_id' => $occPast->id, 'member_id' => $member->id],
                    ['status' => 'confirmed', 'position' => null]
                );
            }
        }

        // --- I: Gia completata (TC-CLS-017: re-complete deve fallire) ---
        ClassOccurrence::firstOrCreate(
            [
                'group_class_id' => $gcPilates->id,
                'date' => Carbon::now()->subDays(5)->toDateString(),
                'start_time' => '07:00:00',
            ],
            [
                'class_schedule_id' => null,
                'end_time' => '08:00:00',
                'trainer_id' => $trainer1->id,
                'capacity' => 10,
                'status' => 'completed',
            ]
        );

        // --- J: Storico profilo atleta — attended (TC-PRF-008 tab corsi) ---
        $occHistoryAttended = ClassOccurrence::firstOrCreate(
            [
                'group_class_id' => $gcYoga->id,
                'date' => Carbon::now()->subDays(10)->toDateString(),
                'start_time' => '09:00:00',
            ],
            [
                'class_schedule_id' => null,
                'end_time' => '10:00:00',
                'trainer_id' => $trainer2->id,
                'capacity' => 10,
                'status' => 'completed',
            ]
        );

        if ($mAtleta) {
            ClassBooking::firstOrCreate(
                ['class_occurrence_id' => $occHistoryAttended->id, 'member_id' => $mAtleta->id],
                [
                    'status' => 'confirmed',
                    'position' => null,
                    'attended_at' => Carbon::now()->subDays(10)->setTime(9, 45),
                ]
            );
        }

        // --- K: Storico profilo atleta — cancellazione (TC-PRF-008 tab corsi) ---
        $occHistoryCancelled = ClassOccurrence::firstOrCreate(
            [
                'group_class_id' => $gcFunctional->id,
                'date' => Carbon::now()->subDays(7)->toDateString(),
                'start_time' => '18:00:00',
            ],
            [
                'class_schedule_id' => null,
                'end_time' => '19:00:00',
                'trainer_id' => $trainer1->id,
                'capacity' => 10,
                'status' => 'planned',
            ]
        );

        if ($mAtleta) {
            ClassBooking::firstOrCreate(
                ['class_occurrence_id' => $occHistoryCancelled->id, 'member_id' => $mAtleta->id],
                ['status' => 'cancelled_by_athlete', 'position' => null]
            );
        }

        // --- L: Trainer overlap PT+corso (REG-003) ---
        $dateOverlap = Carbon::now()->addDays(5)->toDateString();

        ClassOccurrence::firstOrCreate(
            [
                'group_class_id' => $gcFunctional->id,
                'date' => $dateOverlap,
                'start_time' => '14:00:00',
            ],
            [
                'class_schedule_id' => null,
                'end_time' => '15:00:00',
                'trainer_id' => $trainer1->id,
                'capacity' => 10,
                'status' => 'planned',
            ]
        );

        if ($mAtleta) {
            PtBooking::firstOrCreate(
                [
                    'trainer_id' => $trainer1->id,
                    'member_id' => $mAtleta->id,
                    'booked_date' => $dateOverlap,
                    'start_time' => '14:00:00',
                ],
                [
                    'end_time' => '15:00:00',
                    'status' => 'confirmed',
                    'session_id' => null,
                    'cancelled_by' => null,
                    'cancellation_reason' => null,
                    'cancellation_deadline' => Carbon::parse($dateOverlap)->subDay()->setTime(20, 0),
                    'notes' => null,
                ]
            );
        }

        // --- M: GroupClass senza occorrenze future (TC-CAT-003: eliminazione senza blocco) ---
        GroupClass::firstOrCreate(
            ['slug' => 'meditazione-attiva'],
            [
                'name' => 'Meditazione Attiva',
                'description' => 'Sessione di meditazione guidata per il recupero.',
                'duration_minutes' => 30,
                'default_capacity' => 20,
                'is_active' => false,
            ]
        );
    }

    /**
     * Aggiunge notifiche non lette extra per atleta@atleta.atleta.
     * R09R31 section crea 1 unread (class_reminder). TC-NOT-003 richiede almeno 2 unread.
     */
    private function scenarioNotifiche(): void
    {
        $athlete = User::where('email', 'atleta@atleta.atleta')->first();

        if (! $athlete) {
            $this->command->warn('NOT: atleta@atleta.atleta non trovato — skip.');

            return;
        }

        $hasWaitlistPromo = DB::table('notifications')
            ->where('notifiable_id', $athlete->id)
            ->whereNull('read_at')
            ->where('data', 'like', '%waitlist_promoted%')
            ->exists();

        if (! $hasWaitlistPromo) {
            DB::table('notifications')->insert([
                'id' => Str::uuid()->toString(),
                'type' => 'App\\Notifications\\WaitlistPromotionNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $athlete->id,
                'data' => json_encode([
                    'type' => 'waitlist_promoted',
                    'message' => "Sei stato promosso dalla lista d'attesa per Yoga Flow.",
                ]),
                'read_at' => null,
                'created_at' => now()->subHours(3),
                'updated_at' => now()->subHours(3),
            ]);
        }

        $hasCancelledUnread = DB::table('notifications')
            ->where('notifiable_id', $athlete->id)
            ->whereNull('read_at')
            ->where('data', 'like', '%class_cancelled%')
            ->exists();

        if (! $hasCancelledUnread) {
            DB::table('notifications')->insert([
                'id' => Str::uuid()->toString(),
                'type' => 'App\\Notifications\\ClassOccurrenceCancelledNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $athlete->id,
                'data' => json_encode([
                    'type' => 'class_cancelled',
                    'message' => 'Il corso Calisthenics di ieri e stato cancellato.',
                ]),
                'read_at' => null,
                'created_at' => now()->subHours(5),
                'updated_at' => now()->subHours(5),
            ]);
        }
    }

    /**
     * Crea tesserati demo per i test check-in rapido.
     * Carlo Accessi (TC-CHK-004): cert valido, abbonamento con accesses_remaining=0.
     * Stefano NoAbb (TC-CHK-003): cert valido, nessun abbonamento.
     */
    private function scenarioCheckIn(): void
    {
        $this->createMemberDemo(
            'carlo.accessi@functional-test.demo',
            'Carlo',
            'Accessi',
            function (Member $member): void {
                $plan = SubscriptionPlan::firstOrCreate(
                    ['name' => 'Carnet 10 Ingressi (Demo)'],
                    [
                        'price_cents' => 8000,
                        'duration_days' => 60,
                        'max_accesses' => 10,
                        'is_active' => true,
                    ]
                );

                Subscription::firstOrCreate(
                    ['member_id' => $member->id, 'plan_id' => $plan->id],
                    [
                        'started_at' => Carbon::now()->subMonth()->toDateString(),
                        'expires_at' => Carbon::now()->addMonths(2)->toDateString(),
                        'status' => 'active',
                        'accesses_used' => 10,
                        'accesses_remaining' => 0,
                    ]
                );
            }
        );

        // Stefano: nessun abbonamento — check-in bloccato per "Nessun abbonamento attivo"
        $this->createMemberDemo('stefano.noabb@functional-test.demo', 'Stefano', 'NoAbb', null);
    }

    /**
     * Crea Giulia Scadenza: abbonamento attivo in scadenza tra 5 giorni.
     * TC-EXP-002 (pannello scadenze), TC-EXP-005 (widget dashboard), TC-SUB-001 (rinnovo rapido).
     */
    private function scenarioAbbonamenti(): void
    {
        $this->createMemberDemo(
            'giulia.scadenza@functional-test.demo',
            'Giulia',
            'Scadenza',
            function (Member $member): void {
                $mensile = SubscriptionPlan::where('name', 'Mensile')->first()
                    ?? SubscriptionPlan::firstOrCreate(
                        ['name' => 'Mensile'],
                        [
                            'price_cents' => 5000,
                            'duration_days' => 30,
                            'max_accesses' => null,
                            'is_active' => true,
                        ]
                    );

                Subscription::firstOrCreate(
                    ['member_id' => $member->id, 'plan_id' => $mensile->id],
                    [
                        'started_at' => Carbon::now()->subDays(25)->toDateString(),
                        'expires_at' => Carbon::now()->addDays(5)->toDateString(),
                        'status' => 'active',
                        'accesses_remaining' => null,
                    ]
                );
            }
        );
    }

    /**
     * Crea User + Member demo con ruolo atleta.
     * Email nel dominio functional-test.demo per riconoscimento immediato.
     *
     * @param  callable(Member): void|null  $subscriptionCallback
     */
    private function createMemberDemo(
        string $email,
        string $firstName,
        string $lastName,
        ?callable $subscriptionCallback
    ): void {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => "{$firstName} {$lastName}",
                'password' => Hash::make('demo1234'),
                'email_verified_at' => now(),
            ]
        );

        if (! $user->hasRole('atleta')) {
            $user->assignRole(Role::findByName('atleta', 'web'));
        }

        $member = Member::firstOrCreate(
            ['email' => $email],
            [
                'user_id' => $user->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'medical_cert_expiry' => Carbon::now()->addMonths(6)->toDateString(),
                'is_active' => true,
            ]
        );

        if ($member->user_id === null) {
            $member->update(['user_id' => $user->id]);
        }

        if ($subscriptionCallback !== null) {
            $subscriptionCallback($member);
        }
    }
}
