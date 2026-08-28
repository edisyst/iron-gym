<?php

namespace Database\Seeders;

use App\Models\BodyMeasurement;
use App\Models\ClassOccurrence;
use App\Models\Exercise;
use App\Models\GroupClass;
use App\Models\Member;
use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\PersonalRecord;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Seeder di volume per dati realistici: paginazione, filtri, KPI gestore, grafici storici.
 * Additivo e idempotente — NON tocca i record dei seeder esistenti.
 * Tutti i record di volume sono identificabili dal dominio email EMAIL_DOMAIN.
 */
class VolumeDemoSeeder extends Seeder
{
    // =========================================================
    // COSTANTI VOLUME — modificabili senza toccare la logica
    // =========================================================

    /** Seed del generatore random: stesso seed = dati deterministici tra esecuzioni */
    private const SEED = 42;

    /** Tesserati volume totali da portare nel DB (comprende quelli preesistenti del dominio) */
    private const MEMBERS_TOTAL = 50;

    /** Quanti tesserati volume hanno account atleta con accesso PWA */
    private const MEMBERS_WITH_ACCOUNT = 35;

    /** Settimane di storico allenamenti / accessi all'indietro da oggi */
    private const HISTORY_WEEKS = 12;

    /** Atleti (su MEMBERS_WITH_ACCOUNT) che ricevono misurazioni corporee mensili */
    private const BODY_MEAS_ATHLETES = 15;

    /** Atleti (su MEMBERS_WITH_ACCOUNT) che ricevono Personal Record storici */
    private const PR_ATHLETES = 20;

    /** Chunk size per insert batch ExerciseSet — bilancia memoria vs. query count */
    private const INSERT_CHUNK = 500;

    /** Dominio email esclusivo per i record di volume — nessun overlap con dati reali o pilota */
    private const EMAIL_DOMAIN = 'volume-demo.test';

    /** Prefisso nome mesociclo volume — usato come guard per l'idempotenza */
    private const MESO_PREFIX = '[VOL]';

    private Generator $faker;

    // =========================================================
    // ENTRY POINT
    // =========================================================

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('VolumeDemoSeeder: vietato in production.');

            return;
        }

        $this->faker = FakerFactory::create('it_IT');
        $this->faker->seed(self::SEED);

        $this->command->info('VolumeDemoSeeder: avvio...');

        $trainer1 = User::where('email', 'trainer@trainer.trainer')->first();
        $trainer2 = User::where('email', 'trainer2@trainer.trainer')->first() ?? $trainer1;

        if (! $trainer1) {
            $this->command->error('trainer@trainer.trainer non trovato — esegui DemoSeeder prima.');

            return;
        }

        $exercises = $this->resolveExercises();
        if (count($exercises) < 8) {
            $this->command->error('Esercizi insufficienti — esegui ExerciseSeeder prima.');

            return;
        }

        $plans = $this->resolvePlans();

        $receptionist = User::where('email', 'receptionist@receptionist.receptionist')->first();

        $volumeMembers = $this->seedMembers($receptionist);
        $this->command->info("  Tesserati volume: {$volumeMembers->count()}");

        $activeSubMap = $this->seedSubscriptions($volumeMembers, $plans, $receptionist);
        $this->command->info('  Abbonamenti: ok');

        $this->seedAccessLogs($volumeMembers, $activeSubMap);
        $this->command->info('  AccessLog: ok');

        $athleteUsers = $volumeMembers
            ->take(self::MEMBERS_WITH_ACCOUNT)
            ->map(fn ($m) => $m->fresh()->user)
            ->filter()
            ->values();

        $this->seedTrainingHistory($athleteUsers, $exercises, $trainer1, $trainer2);
        $this->command->info('  Storico allenamenti: ok');

        $this->seedBodyMeasurements($athleteUsers);
        $this->command->info('  Misurazioni corporee: ok');

        $this->seedPersonalRecords($athleteUsers, $exercises);
        $this->command->info('  Personal Record: ok');

        $this->seedClassHistory($volumeMembers, $trainer1, $trainer2);
        $this->command->info('  Storico corsi collettivi: ok');

        $this->seedPtBookingHistory($volumeMembers, $trainer1, $trainer2);
        $this->command->info('  Storico prenotazioni PT: ok');

        $this->seedMessages($athleteUsers, $trainer1, $trainer2);
        $this->command->info('  Messaggi: ok');

        $this->printSummary($volumeMembers, $athleteUsers);
    }

    // =========================================================
    // TESSERATI
    // =========================================================

    /** @return Collection<int, Member> */
    private function seedMembers(?User $receptionist): Collection
    {
        $existing = Member::where('email', 'like', '%@'.self::EMAIL_DOMAIN)->get();

        $toCreate = max(0, self::MEMBERS_TOTAL - $existing->count());

        if ($toCreate === 0) {
            return $existing;
        }

        $athleteRole = Role::findByName('atleta', 'web');

        $firstNames = [
            'Luca', 'Marco', 'Sara', 'Giulia', 'Andrea', 'Matteo', 'Chiara', 'Elena',
            'Federico', 'Davide', 'Valentina', 'Alessandro', 'Silvia', 'Lorenzo', 'Martina',
            'Stefano', 'Francesca', 'Nicola', 'Alice', 'Paolo', 'Roberta', 'Giorgio',
            'Elisa', 'Riccardo', 'Cristina', 'Simone', 'Beatrice', 'Fabio', 'Monica',
            'Antonio', 'Laura', 'Daniele', 'Sofia', 'Emanuele', 'Claudia', 'Giacomo',
            'Ilaria', 'Roberto', 'Veronica', 'Filippo', 'Diana', 'Massimo',
        ];

        $lastNames = [
            'Rossi', 'Bianchi', 'Ferrari', 'Russo', 'Esposito', 'Romano', 'Colombo',
            'Ricci', 'Marino', 'Greco', 'Bruno', 'Gallo', 'Conti', 'De Luca', 'Mancini',
            'Costa', 'Giordano', 'Rizzo', 'Lombardi', 'Moretti', 'Barbieri', 'Fontana',
            'Santoro', 'Marini', 'Rinaldi', 'Caruso', 'Ferrara', 'Gatti', 'Pellegrini', 'Palumbo',
        ];

        $usedEmails = [];
        $created = collect();
        $offset = $existing->count();

        for ($i = 0; $i < $toCreate; $i++) {
            $fn = $firstNames[($offset + $i) % count($firstNames)];
            $ln = $lastNames[($offset + $i) % count($lastNames)];
            $base = strtolower("{$fn}.{$ln}");
            $n = 0;

            do {
                $email = ($n === 0 ? $base : "{$base}.{$n}").'@'.self::EMAIL_DOMAIN;
                $n++;
            } while (isset($usedEmails[$email]));

            $usedEmails[$email] = true;

            // Distribuzione certificati medici: 70% valido, 20% in scadenza <30gg, 10% scaduto
            $certExpiry = match (true) {
                ($offset + $i) % 10 === 9 => Carbon::now()->subDays($this->faker->numberBetween(1, 30)),
                ($offset + $i) % 5 === 4 => Carbon::now()->addDays($this->faker->numberBetween(1, 28)),
                default => Carbon::now()->addMonths($this->faker->numberBetween(2, 11)),
            };

            $member = Member::firstOrCreate(
                ['email' => $email],
                [
                    'first_name' => $fn,
                    'last_name' => $ln,
                    'phone' => $this->faker->phoneNumber(),
                    'date_of_birth' => $this->faker->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
                    'medical_cert_expiry' => $certExpiry->toDateString(),
                    'is_active' => true,
                ]
            );

            // I primi MEMBERS_WITH_ACCOUNT ottengono account atleta PWA
            $totalIdx = $offset + $i;

            if ($totalIdx < self::MEMBERS_WITH_ACCOUNT) {
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => "$fn $ln",
                        'password' => Hash::make('demo1234'),
                        'email_verified_at' => now(),
                    ]
                );

                if (! $user->hasRole('atleta')) {
                    $user->assignRole($athleteRole);
                }

                if ($member->user_id === null) {
                    $member->update(['user_id' => $user->id]);
                }
            }

            $created->push($member);
        }

        return $existing->merge($created);
    }

    // =========================================================
    // ABBONAMENTI
    // =========================================================

    /**
     * Crea abbonamenti per i tesserati volume con distribuzione realistica.
     * Usa DB::table per evitare i flush ripetuti di SubscriptionObserver.
     *
     * @param  Collection<int, Member>  $members
     * @param  array<string, array{id: int, duration_days: int, max_accesses: int|null}>  $plans
     * @return array<int, int> mappa member_id → subscription_id (solo attivi con scadenza futura)
     */
    private function seedSubscriptions(Collection $members, array $plans, ?User $receptionist): array
    {
        $activeSubMap = [];
        $planKeys = array_keys($plans);

        foreach ($members as $i => $member) {
            // Recupera sub esistente se già presente
            $existingSub = DB::table('subscriptions')
                ->where('member_id', $member->id)
                ->orderByDesc('expires_at')
                ->first();

            if ($existingSub !== null) {
                if ($existingSub->status === 'active' && Carbon::parse($existingSub->expires_at)->isFuture()) {
                    $activeSubMap[$member->id] = $existingSub->id;
                }

                continue;
            }

            // Distribuzione segmenti su 100: 7% nessun abb, 12% scaduto, 6% sospeso, 15% in scadenza, 60% attivo
            $seg = ($i * 7 + 13) % 100;

            if ($seg < 7) {
                // Nessun abbonamento (mai rinnovato)
                continue;
            }

            $planName = $planKeys[$i % count($planKeys)];
            $plan = $plans[$planName];

            [$status, $startedAt, $expiresAt] = match (true) {
                $seg < 19 => [
                    'expired',
                    Carbon::now()->subDays($plan['duration_days'] + $this->faker->numberBetween(10, 40)),
                    Carbon::now()->subDays($this->faker->numberBetween(1, 20)),
                ],
                $seg < 25 => [
                    'suspended',
                    Carbon::now()->subDays($this->faker->numberBetween(20, 60)),
                    Carbon::now()->addDays($this->faker->numberBetween(5, 30)),
                ],
                $seg < 40 => [
                    'active',
                    Carbon::now()->subDays($plan['duration_days'] - $this->faker->numberBetween(1, 28)),
                    Carbon::now()->addDays($this->faker->numberBetween(1, 28)),
                ],
                default => [
                    'active',
                    Carbon::now()->subDays($this->faker->numberBetween(5, 40)),
                    Carbon::now()->addDays($plan['duration_days'] - $this->faker->numberBetween(5, 40)),
                ],
            };

            $subId = DB::table('subscriptions')->insertGetId([
                'member_id' => $member->id,
                'plan_id' => $plan['id'],
                'started_at' => $startedAt->toDateString(),
                'expires_at' => $expiresAt->toDateString(),
                'status' => $status,
                'accesses_used' => 0,
                'accesses_remaining' => $plan['max_accesses'],
                'notes' => null,
                'created_by' => $receptionist?->id,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]);

            if ($status === 'active' && $expiresAt->isFuture()) {
                $activeSubMap[$member->id] = $subId;
            }
        }

        return $activeSubMap;
    }

    // =========================================================
    // ACCESS LOG
    // =========================================================

    /**
     * @param  Collection<int, Member>  $members
     * @param  array<int, int>  $activeSubMap
     */
    private function seedAccessLogs(Collection $members, array $activeSubMap): void
    {
        $athleteMembers = $members->take(self::MEMBERS_WITH_ACCOUNT);

        $existingCount = DB::table('access_logs')
            ->whereIn('member_id', $athleteMembers->pluck('id'))
            ->count();

        // Guard idempotenza: se ci sono già log significativi, skip
        if ($existingCount > 50) {
            return;
        }

        // Fasce orarie di punta: mattina, pranzo, sera
        $peakHours = [7, 7, 8, 8, 9, 12, 13, 18, 18, 19, 19, 20, 21];

        $rows = [];
        $now = Carbon::now();

        foreach ($athleteMembers as $i => $member) {
            if (! isset($activeSubMap[$member->id])) {
                continue;
            }
            $subId = $activeSubMap[$member->id];

            // Tipo atleta in base all'indice: assiduo, saltuario, dormiente
            $isAssiduo = $i < 12;
            $isSaltuario = $i >= 12 && $i < 24;

            for ($week = self::HISTORY_WEEKS; $week >= 1; $week--) {
                $weekStart = $now->copy()->subWeeks($week)->startOfWeek();

                // Dormienti: 1 ingresso ogni 3 settimane
                if (! $isAssiduo && ! $isSaltuario) {
                    if ($week % 3 !== 0) {
                        continue;
                    }
                    $weekAccesses = 1;
                } else {
                    $weekAccesses = $isAssiduo
                        ? $this->faker->numberBetween(3, 5)
                        : $this->faker->numberBetween(1, 2);
                }

                $usedDays = [];

                for ($a = 0; $a < $weekAccesses; $a++) {
                    $attempts = 0;
                    do {
                        $dayOffset = $this->faker->numberBetween(0, 5); // lun-sab
                        $attempts++;
                    } while (in_array($dayOffset, $usedDays, true) && $attempts < 8);
                    $usedDays[] = $dayOffset;

                    $hour = $peakHours[$this->faker->numberBetween(0, count($peakHours) - 1)];
                    $minute = $this->faker->numberBetween(0, 59);
                    $checkedIn = $weekStart->copy()->addDays($dayOffset)->setHour($hour)->setMinute($minute)->setSecond(0);

                    if ($checkedIn->isFuture()) {
                        continue;
                    }

                    $rows[] = [
                        'member_id' => $member->id,
                        'subscription_id' => $subId,
                        'checked_in_at' => $checkedIn->toDateTimeString(),
                        'checked_in_by' => null,
                        'note' => null,
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('access_logs')->insert($chunk);
        }
    }

    // =========================================================
    // STORICO ALLENAMENTI
    // =========================================================

    /**
     * Crea 3 mesocicli (4 settimane ciascuno) per ogni atleta.
     * - Meso 1 e 2: completati (tutte le sessioni in passato).
     * - Meso 3: completato o attivo a seconda dell'indice atleta.
     * Usa insertGetId per sessions/session_exercises; batch insert per exercise_sets.
     *
     * @param  Collection<int, User>  $athletes
     * @param  array<string, Exercise>  $exercises
     */
    private function seedTrainingHistory(
        Collection $athletes,
        array $exercises,
        User $trainer1,
        User $trainer2
    ): void {
        $trainers = [$trainer1, $trainer2];

        // Piano PPL — ogni entry: chiave esercizio, peso base, step/settimana, reps, bodyweight
        $ppl = [
            'push' => [
                ['key' => 'bench',   'base' => 80.0,  'step' => 2.5,  'reps' => 8,  'bw' => false],
                ['key' => 'incline', 'base' => 60.0,  'step' => 2.5,  'reps' => 10, 'bw' => false],
                ['key' => 'ohp',     'base' => 50.0,  'step' => 2.5,  'reps' => 8,  'bw' => false],
                ['key' => 'lateral', 'base' => 14.0,  'step' => 0.0,  'reps' => 15, 'bw' => false],
            ],
            'pull' => [
                ['key' => 'deadlift', 'base' => 120.0, 'step' => 5.0,  'reps' => 5,  'bw' => false],
                ['key' => 'row',      'base' => 70.0,  'step' => 2.5,  'reps' => 8,  'bw' => false],
                ['key' => 'pullup',   'base' => null,  'step' => 0.0,  'reps' => 7,  'bw' => true],
                ['key' => 'curl',     'base' => 30.0,  'step' => 1.25, 'reps' => 10, 'bw' => false],
            ],
            'legs' => [
                ['key' => 'squat',    'base' => 100.0, 'step' => 2.5, 'reps' => 8,  'bw' => false],
                ['key' => 'leg_press', 'base' => 160.0, 'step' => 5.0, 'reps' => 12, 'bw' => false],
                ['key' => 'leg_curl', 'base' => 40.0,  'step' => 2.5, 'reps' => 10, 'bw' => false],
            ],
        ];

        // Offsets mesocicli: quante settimane fa inizia ciascun meso (3 meso × 4 settimane = 12 settimane)
        $mesoStartOffsets = [12, 8, 4];

        foreach ($athletes as $i => $athlete) {
            if (Mesocycle::where('athlete_id', $athlete->id)
                ->where('name', 'like', self::MESO_PREFIX.'%')
                ->exists()
            ) {
                continue;
            }

            $trainer = $trainers[$i % 2];

            // Moltiplicatore forza distribuito su 3 fasce (beginner / intermediate / advanced)
            $mult = match ($i % 3) {
                0 => 0.75 + ($i % 5) * 0.04,
                1 => 1.0 + ($i % 4) * 0.05,
                default => 1.2 + ($i % 3) * 0.04,
            };

            $setRows = [];

            foreach ($mesoStartOffsets as $mesoIdx => $offsetWeeks) {
                $startDate = Carbon::now()->subWeeks($offsetWeeks)->startOfWeek();
                $isLastMeso = $mesoIdx === 2;
                $mesoCompleted = ! $isLastMeso || ($i % 3 !== 0);
                $mesoStatus = $mesoCompleted ? 'completed' : 'active';

                $meso = Mesocycle::create([
                    'athlete_id' => $athlete->id,
                    'trainer_id' => $trainer->id,
                    'template_id' => null,
                    'name' => self::MESO_PREFIX.' Fase '.($mesoIdx + 1),
                    'goal' => 'hypertrophy',
                    'periodization_model' => 'linear',
                    'start_date' => $startDate->toDateString(),
                    'weeks_count' => 4,
                    'status' => $mesoStatus,
                ]);

                for ($weekNum = 1; $weekNum <= 4; $weekNum++) {
                    $weekStart = $startDate->copy()->addWeeks($weekNum - 1);
                    $isDeload = ($weekNum === 4);

                    $week = MicrocycleWeek::create([
                        'mesocycle_id' => $meso->id,
                        'week_number' => $weekNum,
                        'is_deload' => $isDeload,
                        'start_date' => $weekStart->toDateString(),
                        'end_date' => $weekStart->copy()->addDays(6)->toDateString(),
                    ]);

                    // Settimana globale per progressione peso cumulativa cross-meso
                    $globalWeek = $mesoIdx * 4 + $weekNum;

                    $sessionOrder = 1;

                    foreach (['push' => 0, 'pull' => 2, 'legs' => 4] as $sessionType => $dayOff) {
                        $sessionDate = $weekStart->copy()->addDays($dayOff)->setHour(18);

                        if ($sessionDate->isFuture() && ! $isLastMeso) {
                            continue;
                        }

                        $isPast = $sessionDate->isPast();
                        $status = match (true) {
                            ! $isPast => 'planned',
                            $this->faker->boolean(10) => 'skipped',
                            default => 'completed',
                        };

                        $startedAt = $status === 'completed' ? $sessionDate->copy()->subMinutes(70) : null;
                        $completedAt = $status === 'completed' ? $sessionDate : null;

                        $sessionId = DB::table('training_sessions')->insertGetId([
                            'microcycle_week_id' => $week->id,
                            'name' => ucfirst($sessionType).' '.chr(64 + $weekNum),
                            'order_in_week' => $sessionOrder++,
                            'scheduled_date' => $sessionDate->toDateString(),
                            'started_at' => $startedAt?->toDateTimeString(),
                            'completed_at' => $completedAt?->toDateTimeString(),
                            'status' => $status,
                            'athlete_notes' => null,
                            'trainer_notes' => null,
                            'created_at' => now()->toDateTimeString(),
                            'updated_at' => now()->toDateTimeString(),
                        ]);

                        if ($status === 'completed' && $this->faker->boolean(60)) {
                            DB::table('session_feedbacks')->insert([
                                'session_id' => $sessionId,
                                'pump' => $isDeload ? 1 : $this->faker->numberBetween(1, 3),
                                'soreness_prev' => $this->faker->numberBetween(0, 2),
                                'perceived_effort' => $isDeload ? 1 : $this->faker->numberBetween(1, 3),
                                'joint_pain' => $this->faker->boolean(15) ? 1 : 0,
                                'performance' => $this->faker->numberBetween(1, 3),
                                'sleep_hours' => $this->faker->randomFloat(1, 6.0, 9.0),
                                'stress_level' => $this->faker->numberBetween(0, 2),
                                'note' => null,
                                'created_at' => $completedAt?->toDateTimeString(),
                            ]);
                        }

                        if ($status !== 'completed') {
                            continue;
                        }

                        foreach ($ppl[$sessionType] as $pos => $exDef) {
                            if (! isset($exercises[$exDef['key']])) {
                                continue;
                            }

                            $ex = $exercises[$exDef['key']];

                            // Arrotonda il peso base al multiplo di 2.5 kg più vicino
                            $isBw = $exDef['bw'];

                            if (! $isBw) {
                                $rawWeight = ($exDef['base'] + ($globalWeek - 1) * $exDef['step']) * $mult;
                                $baseWeight = round($rawWeight / 2.5) * 2.5;
                            } else {
                                $baseWeight = null;
                            }

                            $workingSets = $isDeload ? 2 : 3;

                            $seId = DB::table('session_exercises')->insertGetId([
                                'session_id' => $sessionId,
                                'group_id' => null,
                                'exercise_id' => $ex->id,
                                'order_in_session' => $pos + 1,
                                'order_in_group' => null,
                                'technique_type' => 'straight',
                                'tempo' => null,
                                'planned_sets_count' => $workingSets + ($baseWeight !== null ? 1 : 0),
                                'planned_rest_sec' => 120,
                                'intra_cluster_rest_sec' => null,
                                'trainer_note' => null,
                            ]);

                            $setIndex = 1;

                            // Warmup (solo esercizi con peso, non BW)
                            if ($baseWeight !== null) {
                                $warmupWeight = round($baseWeight * 0.6 / 2.5) * 2.5;
                                $setRows[] = $this->buildSetRow(
                                    $seId, $setIndex++, true,
                                    $exDef['reps'], $warmupWeight, null,
                                    $exDef['reps'], $warmupWeight, null,
                                    $startedAt->copy()->addMinutes($pos * 15 + 2)
                                );
                            }

                            // Working sets
                            for ($s = 0; $s < $workingSets; $s++) {
                                $actualReps = $isBw
                                    ? max(1, $exDef['reps'] + ($globalWeek - 1) + $this->faker->numberBetween(-1, 1))
                                    : max(1, $exDef['reps'] + $this->faker->numberBetween(-1, 1));

                                $actualRir = $isDeload ? 3 : ($s === $workingSets - 1
                                    ? $this->faker->numberBetween(0, 1)
                                    : $this->faker->numberBetween(1, 2));

                                $setRows[] = $this->buildSetRow(
                                    $seId, $setIndex++, false,
                                    $exDef['reps'], $baseWeight, $isDeload ? 3 : 2,
                                    $actualReps, $baseWeight, $actualRir,
                                    $startedAt->copy()->addMinutes($pos * 15 + 5 + $s * 4)
                                );
                            }

                            // Flush parziale
                            if (count($setRows) >= self::INSERT_CHUNK) {
                                DB::table('exercise_sets')->insert(
                                    array_splice($setRows, 0, self::INSERT_CHUNK)
                                );
                            }
                        }
                    }
                }
            }

            // Flush rimanenti per questo atleta
            foreach (array_chunk($setRows, self::INSERT_CHUNK) as $chunk) {
                DB::table('exercise_sets')->insert($chunk);
            }
        }
    }

    // =========================================================
    // MISURAZIONI CORPOREE
    // =========================================================

    /** @param Collection<int, User> $athletes */
    private function seedBodyMeasurements(Collection $athletes): void
    {
        foreach ($athletes->take(self::BODY_MEAS_ATHLETES) as $athlete) {
            if (BodyMeasurement::where('athlete_id', $athlete->id)->exists()) {
                continue;
            }

            $baseWeight = $this->faker->randomFloat(1, 65.0, 110.0);
            $trend = $this->faker->randomFloat(2, -0.3, 0.1);
            $rows = [];

            // Misura mensile: ogni 4 settimane
            for ($week = self::HISTORY_WEEKS; $week >= 4; $week -= 4) {
                $measDate = Carbon::now()->subWeeks($week)->addDay();
                $weight = round($baseWeight + $trend * (self::HISTORY_WEEKS - $week), 1);

                $rows[] = [
                    'athlete_id' => $athlete->id,
                    'measured_at' => $measDate->toDateString(),
                    'weight_kg' => $weight,
                    'body_fat_pct' => $this->faker->boolean(60) ? $this->faker->randomFloat(1, 10.0, 28.0) : null,
                    'waist_cm' => $this->faker->boolean(70) ? $this->faker->randomFloat(1, 68.0, 95.0) : null,
                    'chest_cm' => $this->faker->boolean(50) ? $this->faker->randomFloat(1, 88.0, 118.0) : null,
                    'hips_cm' => null,
                    'left_arm_cm' => $this->faker->boolean(40) ? $this->faker->randomFloat(1, 30.0, 48.0) : null,
                    'right_arm_cm' => $this->faker->boolean(40) ? $this->faker->randomFloat(1, 30.0, 48.0) : null,
                    'left_thigh_cm' => null,
                    'right_thigh_cm' => null,
                    'left_calf_cm' => null,
                    'right_calf_cm' => null,
                    'notes' => null,
                    'recorded_by' => null,
                    'created_at' => $measDate->toDateTimeString(),
                    'updated_at' => $measDate->toDateTimeString(),
                ];
            }

            DB::table('body_measurements')->insert($rows);
        }
    }

    // =========================================================
    // PERSONAL RECORD
    // =========================================================

    /**
     * Inserisce 4 PR storici per 5 esercizi compound su PR_ATHLETES atleti.
     * L'e1rm finale è calcolato dai set reali creati da seedTrainingHistory.
     * Insert diretto — PersonalRecordDetector non è un observer, non scatta.
     *
     * @param  Collection<int, User>  $athletes
     * @param  array<string, Exercise>  $exercises
     */
    private function seedPersonalRecords(Collection $athletes, array $exercises): void
    {
        $prExKeys = ['bench', 'deadlift', 'squat', 'ohp', 'curl'];

        foreach ($athletes->take(self::PR_ATHLETES) as $athlete) {
            if (PersonalRecord::where('athlete_id', $athlete->id)->exists()) {
                continue;
            }

            foreach ($prExKeys as $key) {
                if (! isset($exercises[$key])) {
                    continue;
                }

                $ex = $exercises[$key];

                // Cerca l'ultimo set working reale dell'atleta per questo esercizio
                $set = DB::table('exercise_sets as es')
                    ->join('session_exercises as se', 'es.session_exercise_id', '=', 'se.id')
                    ->join('training_sessions as ts', 'se.session_id', '=', 'ts.id')
                    ->join('microcycle_weeks as mw', 'ts.microcycle_week_id', '=', 'mw.id')
                    ->join('mesocycles as mc', 'mw.mesocycle_id', '=', 'mc.id')
                    ->where('mc.athlete_id', $athlete->id)
                    ->where('se.exercise_id', $ex->id)
                    ->where('es.is_warmup', 0)
                    ->whereNotNull('es.actual_weight_kg')
                    ->whereNotNull('es.actual_reps')
                    ->orderByDesc('es.id')
                    ->select('es.id', 'es.actual_weight_kg', 'es.actual_reps', 'es.completed_at')
                    ->first();

                if (! $set || (float) $set->actual_reps < 1 || $set->actual_weight_kg === null) {
                    continue;
                }

                // Epley: weight * (1 + reps/30)
                $finalE1rm = round((float) $set->actual_weight_kg * (1 + (int) $set->actual_reps / 30.0), 2);
                $startE1rm = round($finalE1rm * 0.85, 2);
                $stepE1rm = ($finalE1rm - $startE1rm) / 3;

                $prRows = [];

                for ($pr = 0; $pr < 4; $pr++) {
                    $value = round($startE1rm + $stepE1rm * $pr, 2);
                    $daysAgo = (3 - $pr) * 21 + $this->faker->numberBetween(0, 5);
                    $achieved = Carbon::now()->subDays($daysAgo);

                    $prRows[] = [
                        'athlete_id' => $athlete->id,
                        'exercise_id' => $ex->id,
                        'exercise_set_id' => $set->id,
                        'record_type' => 'e1rm',
                        'value' => $value,
                        'achieved_at' => $achieved->toDateTimeString(),
                        'created_at' => $achieved->toDateTimeString(),
                        'updated_at' => $achieved->toDateTimeString(),
                    ];
                }

                DB::table('personal_records')->insert($prRows);
            }
        }
    }

    // =========================================================
    // STORICO CORSI COLLETTIVI
    // =========================================================

    /** @param Collection<int, Member> $members */
    private function seedClassHistory(Collection $members, User $trainer1, User $trainer2): void
    {
        $groupClasses = GroupClass::where('is_active', true)->get();

        if ($groupClasses->isEmpty()) {
            $this->command->warn('  Nessun GroupClass attivo — skip storico corsi (esegui GroupClassSeeder).');

            return;
        }

        $trainers = [$trainer1, $trainer2];
        $membersArr = $members->all();
        $now = Carbon::now();

        // Ora fissa per tipo di corso (varia per non sovrapporsi con FunctionalTestSeeder)
        $classTimes = ['07:30', '09:30', '11:00', '17:30', '19:00', '20:30'];

        foreach ($groupClasses as $gcIdx => $gc) {
            $startTime = $classTimes[$gcIdx % count($classTimes)].':00';
            $endHour = (int) substr($startTime, 0, 2) + (int) ceil(($gc->duration_minutes ?? 60) / 60);
            $endTime = sprintf('%02d:%s', $endHour, substr($startTime, 3));

            for ($week = self::HISTORY_WEEKS; $week >= 1; $week--) {
                $monday = $now->copy()->subWeeks($week)->startOfWeek();
                $dayOffset = $gcIdx % 6; // lun-sab, fisso per corso
                $occDate = $monday->copy()->addDays($dayOffset);

                if ($occDate->isFuture()) {
                    continue;
                }

                $exists = DB::table('class_occurrences')
                    ->where('group_class_id', $gc->id)
                    ->whereDate('date', $occDate->toDateString())
                    ->where('start_time', $startTime)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $trainer = $trainers[$gcIdx % 2];
                $capacity = $gc->default_capacity ?? 10;

                $occurrence = ClassOccurrence::create([
                    'group_class_id' => $gc->id,
                    'class_schedule_id' => null,
                    'date' => $occDate->toDateString(),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'trainer_id' => $trainer->id,
                    'capacity' => $capacity,
                    'status' => 'completed',
                ]);

                // Distribuzione fill rate realistico
                $fillPct = match ($week % 10) {
                    0, 1, 2 => 0,
                    3, 4, 5, 6 => $this->faker->numberBetween(30, 70),
                    7, 8 => 100,
                    default => 115, // waitlist
                };

                $totalSlots = max(0, (int) round($capacity * $fillPct / 100));
                $confirmedCount = min($totalSlots, $capacity);
                $waitlistCount = max(0, $totalSlots - $confirmedCount);

                // Seleziona partecipanti ruotando sull'array members
                $startIdx = ($gcIdx * 7 + $week * 3) % count($membersArr);
                $bookingRows = [];

                for ($p = 0; $p < $confirmedCount; $p++) {
                    $member = $membersArr[($startIdx + $p) % count($membersArr)];

                    // Salta se già iscritto a quest'occorrenza
                    $alreadyBooked = DB::table('class_bookings')
                        ->where('class_occurrence_id', $occurrence->id)
                        ->where('member_id', $member->id)
                        ->exists();

                    if ($alreadyBooked) {
                        continue;
                    }

                    $attendedAt = $this->faker->boolean(80)
                        ? $occDate->copy()->setTime((int) substr($startTime, 0, 2), $this->faker->numberBetween(5, 30))
                        : null;

                    $bookingRows[] = [
                        'class_occurrence_id' => $occurrence->id,
                        'member_id' => $member->id,
                        'status' => 'confirmed',
                        'position' => null,
                        'attended_at' => $attendedAt?->toDateTimeString(),
                        'booked_by' => null,
                        'created_at' => $occDate->copy()->subDays(3)->toDateTimeString(),
                        'updated_at' => $occDate->copy()->subDays(3)->toDateTimeString(),
                    ];
                }

                for ($w = 0; $w < $waitlistCount; $w++) {
                    $member = $membersArr[($startIdx + $confirmedCount + $w) % count($membersArr)];

                    $alreadyBooked = DB::table('class_bookings')
                        ->where('class_occurrence_id', $occurrence->id)
                        ->where('member_id', $member->id)
                        ->exists();

                    if ($alreadyBooked) {
                        continue;
                    }

                    $bookingRows[] = [
                        'class_occurrence_id' => $occurrence->id,
                        'member_id' => $member->id,
                        'status' => 'waitlisted',
                        'position' => $w + 1,
                        'attended_at' => null,
                        'booked_by' => null,
                        'created_at' => $occDate->copy()->subDays(3)->toDateTimeString(),
                        'updated_at' => $occDate->copy()->subDays(3)->toDateTimeString(),
                    ];
                }

                if (! empty($bookingRows)) {
                    DB::table('class_bookings')->insert($bookingRows);
                }
            }
        }
    }

    // =========================================================
    // PRENOTAZIONI PT — STORICO
    // =========================================================

    /** @param Collection<int, Member> $members */
    private function seedPtBookingHistory(Collection $members, User $trainer1, User $trainer2): void
    {
        $athleteMembers = $members->take(self::MEMBERS_WITH_ACCOUNT);
        $memberIds = $athleteMembers->pluck('id')->all();

        $existingCount = DB::table('pt_bookings')
            ->whereIn('member_id', $memberIds)
            ->count();

        if ($existingCount > 0) {
            return;
        }

        $trainers = [$trainer1, $trainer2];
        $membersArr = $athleteMembers->all();
        $now = Carbon::now();
        $rows = [];
        $slotGuard = [];

        for ($week = self::HISTORY_WEEKS; $week >= 0; $week--) {
            $monday = $now->copy()->subWeeks($week)->startOfWeek();

            foreach ($trainers as $t => $trainer) {
                // 3 slot PT a settimana per trainer
                $slots = [[9, 0], [10, 2], [15, 3]]; // [ora, dayOffset]

                foreach ($slots as [$hour, $dayOffset]) {
                    $bookDate = $monday->copy()->addDays($dayOffset);

                    // Non creare prenotazioni per oggi o futuro prossimo (già gestite da BookingDemoSeeder)
                    if ($bookDate->isFuture() || $bookDate->isToday()) {
                        continue;
                    }

                    $slotKey = "{$trainer->id}:{$bookDate->toDateString()}:{$hour}";
                    if (isset($slotGuard[$slotKey])) {
                        continue;
                    }
                    $slotGuard[$slotKey] = true;

                    $memberIdx = ($week * 6 + $t * 3 + $hour) % count($membersArr);
                    $member = $membersArr[$memberIdx];

                    $status = $this->faker->boolean(15) ? 'cancelled' : 'completed';

                    $rows[] = [
                        'trainer_id' => $trainer->id,
                        'member_id' => $member->id,
                        'session_id' => null,
                        'booked_date' => $bookDate->toDateString(),
                        'start_time' => sprintf('%02d:00:00', $hour),
                        'end_time' => sprintf('%02d:00:00', $hour + 1),
                        'status' => $status,
                        'cancelled_by' => $status === 'cancelled' ? 'athlete' : null,
                        'cancellation_reason' => $status === 'cancelled' ? 'Impegno personale' : null,
                        'cancellation_deadline' => $bookDate->copy()->subDay()->setTime(20, 0)->toDateTimeString(),
                        'notes' => null,
                        'created_at' => $bookDate->copy()->subDays(3)->toDateTimeString(),
                        'updated_at' => $bookDate->copy()->subDays(3)->toDateTimeString(),
                    ];
                }
            }
        }

        // Batch insert senza Eloquent (nessun observer)
        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('pt_bookings')->insert($chunk);
        }
    }

    // =========================================================
    // MESSAGGI
    // =========================================================

    /** @param Collection<int, User> $athletes */
    private function seedMessages(Collection $athletes, User $trainer1, User $trainer2): void
    {
        $trainers = [$trainer1, $trainer2];

        $trainerLines = [
            'Come vanno gli allenamenti questa settimana?',
            'Ottimi progressi, continua cosi.',
            'Ricordati di fare stretching post-sessione.',
            'Per la prossima settimana aumentiamo i carichi del 5%.',
            'Come ti senti con i carichi attuali?',
        ];

        $athleteLines = [
            'Tutto bene, mi sento in forma.',
            'La seduta di ieri e andata benissimo.',
            'Ho un dubbio sulla tecnica dello squat.',
            'Grazie per i consigli, li sto applicando.',
            'Qual e il prossimo step del programma?',
        ];

        foreach ($athletes->take(20) as $i => $athlete) {
            $trainer = $trainers[$i % 2];

            $exists = DB::table('messages')
                ->where('sender_id', $trainer->id)
                ->where('receiver_id', $athlete->id)
                ->exists();

            if ($exists) {
                continue;
            }

            $msgCount = $this->faker->numberBetween(2, 5);
            $baseDate = Carbon::now()->subDays($this->faker->numberBetween(3, 30));

            for ($m = 0; $m < $msgCount; $m++) {
                $isTrainerSend = ($m % 2 === 0);
                $sender = $isTrainerSend ? $trainer : $athlete;
                $receiver = $isTrainerSend ? $athlete : $trainer;
                $msgDate = $baseDate->copy()->addHours($m * 3 + $this->faker->numberBetween(0, 4));
                $isOld = $msgDate->lt(Carbon::now()->subDays(7));

                DB::table('messages')->insert([
                    'sender_id' => $sender->id,
                    'receiver_id' => $receiver->id,
                    'body' => $isTrainerSend
                        ? $trainerLines[$m % count($trainerLines)]
                        : $athleteLines[$m % count($athleteLines)],
                    'read_at' => $isOld ? $msgDate->copy()->addMinutes(15)->toDateTimeString() : null,
                    'created_at' => $msgDate->toDateTimeString(),
                    'updated_at' => $msgDate->toDateTimeString(),
                ]);
            }
        }
    }

    // =========================================================
    // RIEPILOGO
    // =========================================================

    /**
     * @param  Collection<int, Member>  $volumeMembers
     * @param  Collection<int, User>  $athleteUsers
     */
    private function printSummary(Collection $volumeMembers, Collection $athleteUsers): void
    {
        $memberIds = $volumeMembers->pluck('id');
        $athleteIds = $athleteUsers->pluck('id');
        $mesoIds = Mesocycle::whereIn('athlete_id', $athleteIds)
            ->where('name', 'like', self::MESO_PREFIX.'%')
            ->pluck('id');
        $weekIds = MicrocycleWeek::whereIn('mesocycle_id', $mesoIds)->pluck('id');
        $sessionIds = DB::table('training_sessions')
            ->whereIn('microcycle_week_id', $weekIds)->pluck('id');
        $seIds = DB::table('session_exercises')
            ->whereIn('session_id', $sessionIds)->pluck('id');

        $this->command->info('');
        $this->command->info('=== VolumeDemoSeeder — riepilogo ===');
        $this->command->info(sprintf('  Tesserati @%-20s %d', self::EMAIL_DOMAIN.':', $memberIds->count()));
        $this->command->info(sprintf('  Abbonamenti volume:         %d', DB::table('subscriptions')->whereIn('member_id', $memberIds)->count()));
        $this->command->info(sprintf('  AccessLog volume:           %d', DB::table('access_logs')->whereIn('member_id', $memberIds)->count()));
        $this->command->info(sprintf('  Mesocicli [VOL]:            %d', $mesoIds->count()));
        $this->command->info(sprintf('  TrainingSession volume:     %d', $sessionIds->count()));
        $this->command->info(sprintf('  ExerciseSet volume:         %d', DB::table('exercise_sets')->whereIn('session_exercise_id', $seIds)->count()));
        $this->command->info(sprintf('  BodyMeasurement volume:     %d', DB::table('body_measurements')->whereIn('athlete_id', $athleteIds)->count()));
        $this->command->info(sprintf('  PersonalRecord volume:      %d', DB::table('personal_records')->whereIn('athlete_id', $athleteIds)->count()));
        $this->command->info(sprintf('  ClassOccurrence totali:     %d', ClassOccurrence::count()));
        $this->command->info(sprintf('  ClassBooking totali:        %d', DB::table('class_bookings')->count()));
        $this->command->info(sprintf('  PtBooking volume:           %d', DB::table('pt_bookings')->whereIn('member_id', $memberIds)->count()));
        $this->command->info(sprintf('  Messaggi volume:            %d', DB::table('messages')->whereIn('receiver_id', $athleteIds)->count()));
        $this->command->info('=====================================');
    }

    // =========================================================
    // HELPER: build set row per batch insert
    // =========================================================

    /**
     * @return array<string, mixed>
     */
    private function buildSetRow(
        int $seId,
        int $setIndex,
        bool $isWarmup,
        int $plannedReps,
        ?float $plannedWeight,
        ?int $plannedRir,
        int $actualReps,
        ?float $actualWeight,
        ?int $actualRir,
        Carbon $completedAt
    ): array {
        return [
            'session_exercise_id' => $seId,
            'set_index' => $setIndex,
            'set_sequence_id' => null,
            'sequence_index' => null,
            'set_subtype' => null,
            'is_warmup' => $isWarmup ? 1 : 0,
            'planned_reps' => $plannedReps,
            'planned_weight_kg' => $plannedWeight,
            'planned_rir' => $plannedRir,
            'planned_rpe' => null,
            'planned_duration_sec' => null,
            'actual_reps' => $actualReps,
            'actual_weight_kg' => $actualWeight,
            'actual_rir' => $actualRir,
            'actual_rpe' => null,
            'actual_duration_sec' => null,
            'completed_at' => $completedAt->toDateTimeString(),
            'note' => null,
        ];
    }

    // =========================================================
    // HELPER: risoluzione esercizi e piani
    // =========================================================

    /**
     * @return array<string, Exercise>
     */
    private function resolveExercises(): array
    {
        $slugs = [
            'bench' => 'barbell_bench_press',
            'incline' => 'incline_barbell_bench_press',
            'ohp' => 'overhead_press_standing',
            'lateral' => 'dumbbell_lateral_raise',
            'deadlift' => 'conventional_deadlift',
            'row' => 'barbell_row',
            'pullup' => 'pull_up_pronated',
            'curl' => 'barbell_curl',
            'squat' => 'back_squat_high_bar',
            'leg_press' => 'leg_press_45',
            'leg_curl' => 'lying_leg_curl',
        ];

        $result = [];
        foreach ($slugs as $key => $slug) {
            $ex = Exercise::where('slug', $slug)->first();
            if ($ex) {
                $result[$key] = $ex;
            }
        }

        return $result;
    }

    /**
     * @return array<string, array{id: int, duration_days: int, max_accesses: int|null}>
     */
    private function resolvePlans(): array
    {
        $plans = [];

        foreach (SubscriptionPlan::where('is_active', true)->get() as $plan) {
            $plans[$plan->name] = [
                'id' => $plan->id,
                'duration_days' => $plan->duration_days,
                'max_accesses' => $plan->max_accesses,
            ];
        }

        if (empty($plans)) {
            $p = SubscriptionPlan::firstOrCreate(
                ['name' => 'Mensile'],
                ['price_cents' => 5000, 'duration_days' => 30, 'max_accesses' => null, 'is_active' => true]
            );
            $plans['Mensile'] = ['id' => $p->id, 'duration_days' => 30, 'max_accesses' => null];
        }

        return $plans;
    }
}
