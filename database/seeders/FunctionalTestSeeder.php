<?php

namespace Database\Seeders;

use App\Models\ClassBooking;
use App\Models\ClassOccurrence;
use App\Models\GroupClass;
use App\Models\Member;
use App\Models\OpeningHour;
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

class FunctionalTestSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('FunctionalTestSeeder: vietato in production.');

            return;
        }

        $this->scenarioCorsiCollettivi();
        $this->scenarioNotifiche();
        $this->scenarioCheckIn();
        $this->scenarioAbbonamenti();
        $this->scenarioOrariApertura();

        $this->command->info('FunctionalTestSeeder completato.');
    }

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
            $this->command->warn('CLS: nessuna GroupClass trovata — esegui GroupClassSeeder prima.');

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
     * R09R31DemoSeeder crea 1 unread (class_reminder). TC-NOT-003 richiede almeno 2 unread.
     * Idempotente: controlla esistenza per tipo prima di inserire.
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
     * Crea orari di apertura settimanali se non esistono record ricorrenti.
     * TC-OPH-001..007 richiedono slot OpeningHour per la visualizzazione iniziale.
     * Convenzione: 0=lunedi, 6=domenica.
     */
    private function scenarioOrariApertura(): void
    {
        $schedule = [
            [0, '09:00:00', '22:00:00'],
            [1, '09:00:00', '22:00:00'],
            [2, '09:00:00', '22:00:00'],
            [3, '09:00:00', '22:00:00'],
            [4, '09:00:00', '22:00:00'],
            [5, '09:00:00', '18:00:00'],
            [6, '09:00:00', '14:00:00'],
        ];

        foreach ($schedule as [$day, $start, $end]) {
            OpeningHour::firstOrCreate(
                ['day_of_week' => $day, 'specific_date' => null],
                [
                    'start_time' => $start,
                    'end_time' => $end,
                    'is_open' => true,
                    'is_annual' => false,
                    'notes' => null,
                ]
            );
        }
    }

    /**
     * Crea User + Member demo con ruolo atleta.
     * Email nel dominio functional-test.demo per riconoscimento immediato.
     * L'opzionale $subscriptionCallback riceve il Member appena creato/trovato.
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
