<?php

namespace Database\Seeders;

use App\Models\BodyMeasurement;
use App\Models\Exercise;
use App\Models\ExerciseSet;
use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\Muscle;
use App\Models\SessionExercise;
use App\Models\SessionFeedback;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Storico allenamenti, mesociclo attivo e progressi demo.
 * Fonde: TrainingHistorySeeder + ActiveMesocycleSeeder + ProgressDemoSeeder.
 * Idempotente: elimina e ricrea i dati per nome mesociclo.
 */
class TrainingDemoSeeder extends Seeder
{
    private const MESO_NAME_HISTORY = '[TEST] Storico';

    private const MESO_NAME_ACTIVE = '[DEMO] PPL Ipertrofia';

    private const MESO_NAMES_PROGRESS = [
        '[DEMO] PPL Fase 1',
        '[DEMO] PPL Fase 2',
        '[DEMO] PPL Fase 3',
        '[DEMO] PPL Fase 4',
        '[DEMO] PPL Fase 5',
    ];

    // Moltiplicatori peso per simulare livelli di forza diversi tra atleti
    private const WEIGHT_MULTIPLIERS = [1.0, 0.85, 1.1, 0.75, 0.95, 1.05];

    public function run(): void
    {
        $athletes = User::role('atleta')->orderBy('id')->get();
        $trainers = User::role('trainer')->orderBy('id')->get();

        if ($athletes->isEmpty()) {
            $this->command->error('Nessun atleta trovato. Esegui prima CoreDemoSeeder.');

            return;
        }

        if ($trainers->isEmpty()) {
            $this->command->error('Nessun trainer trovato. Esegui prima CoreDemoSeeder.');

            return;
        }

        $exercises = $this->resolveExercises();

        if (count($exercises) < 6) {
            $this->command->error('Esercizi non trovati nel DB. Esegui prima ExerciseSeeder.');

            return;
        }

        $this->seedHistory($athletes, $trainers, $exercises);
        $this->seedActiveMesocycles($athletes, $trainers, $exercises);
        $this->seedProgress($exercises);
    }

    // -------------------------------------------------------------------------
    // Shared
    // -------------------------------------------------------------------------

    /** @return array<string, Exercise> */
    private function resolveExercises(): array
    {
        $slugs = [
            'bench' => 'barbell_bench_press',
            'ohp' => 'overhead_press_standing',
            'incline' => 'incline_barbell_bench_press',
            'lateral' => 'dumbbell_lateral_raise',
            'deadlift' => 'conventional_deadlift',
            'row' => 'barbell_row',
            'pullup' => 'pull_up_pronated',
            'curl' => 'barbell_curl',
            'squat' => 'back_squat_high_bar',
            'leg_press' => 'leg_press_45',
            'leg_curl' => 'lying_leg_curl',
            'crunch' => 'floor_crunch',
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
     * Set working con warmup al 60%. Usato da tutte e tre le sezioni.
     *
     * @return list<array<string, mixed>>
     */
    private function straightSets(int $week, float $baseWeight, float $step, int $reps, float $mult = 1.0): array
    {
        $w = round(($baseWeight + ($week - 1) * $step) * $mult, 2);
        $warmup = round($w * 0.6, 1);

        return [
            ['warmup' => true,  'reps' => $reps, 'weight' => $warmup, 'rir' => null],
            ['warmup' => false, 'reps' => $reps, 'weight' => $w,      'rir' => 3],
            ['warmup' => false, 'reps' => $reps, 'weight' => $w,      'rir' => 2],
            ['warmup' => false, 'reps' => $reps, 'weight' => $w,      'rir' => 1],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function bwSets(int $week): array
    {
        $reps = 6 + ($week - 1);

        return [
            ['warmup' => false, 'reps' => $reps,     'weight' => null, 'rir' => 3],
            ['warmup' => false, 'reps' => $reps,     'weight' => null, 'rir' => 2],
            ['warmup' => false, 'reps' => $reps - 1, 'weight' => null, 'rir' => 1],
        ];
    }

    // -------------------------------------------------------------------------
    // Sezione 1 — Storico allenamenti (ex TrainingHistorySeeder)
    // -------------------------------------------------------------------------

    /** @param array<string, Exercise> $exercises */
    private function seedHistory(Collection $athletes, Collection $trainers, array $exercises): void
    {
        foreach ($athletes as $i => $athlete) {
            Mesocycle::where('athlete_id', $athlete->id)
                ->where('name', self::MESO_NAME_HISTORY)
                ->get()
                ->each(fn ($m) => $m->delete());

            $trainer = $trainers[$i % $trainers->count()];
            $mult = self::WEIGHT_MULTIPLIERS[$i % count(self::WEIGHT_MULTIPLIERS)];

            DB::transaction(function () use ($athlete, $trainer, $exercises, $mult): void {
                $startDate = Carbon::now()->subWeeks(6)->startOfWeek();

                $mesocycle = Mesocycle::create([
                    'athlete_id' => $athlete->id,
                    'trainer_id' => $trainer->id,
                    'template_id' => null,
                    'name' => self::MESO_NAME_HISTORY,
                    'goal' => 'hypertrophy',
                    'periodization_model' => 'linear',
                    'start_date' => $startDate,
                    'weeks_count' => 4,
                    'status' => 'completed',
                ]);

                for ($weekNum = 1; $weekNum <= 4; $weekNum++) {
                    $weekStart = $startDate->copy()->addDays(($weekNum - 1) * 7);
                    $week = MicrocycleWeek::create([
                        'mesocycle_id' => $mesocycle->id,
                        'week_number' => $weekNum,
                        'is_deload' => ($weekNum === 4),
                        'start_date' => $weekStart,
                        'end_date' => $weekStart->copy()->addDays(6),
                    ]);

                    $pushAt = $weekStart->copy()->setHour(18)->setMinute(0);
                    $this->historySession($week, 'Push A', 1, $pushAt, [
                        ['exercise' => $exercises['bench'],   'sets' => $this->straightSets($weekNum, 80.0, 2.5, 10, $mult)],
                        ['exercise' => $exercises['ohp'],     'sets' => $this->straightSets($weekNum, 50.0, 2.5, 10, $mult)],
                        ['exercise' => $exercises['incline'], 'sets' => $this->straightSets($weekNum, 60.0, 2.5, 10, $mult)],
                    ], $weekNum);

                    $pullAt = $weekStart->copy()->addDays(2)->setHour(18)->setMinute(0);
                    $this->historySession($week, 'Pull B', 2, $pullAt, [
                        ['exercise' => $exercises['deadlift'], 'sets' => $this->historyPullSets($weekNum, 120.0, 5.0, $mult)],
                        ['exercise' => $exercises['pullup'],   'sets' => $this->bwSets($weekNum)],
                        ['exercise' => $exercises['curl'],     'sets' => $this->straightSets($weekNum, 30.0, 1.25, 10, $mult)],
                    ], $weekNum);
                }
            });

            $this->command->info("TrainingDemoSeeder: storico creato per {$athlete->email} (trainer: {$trainer->name})");
        }
    }

    /**
     * Warmup al 50% per esercizi di trazione pesante (stacco).
     *
     * @return list<array<string, mixed>>
     */
    private function historyPullSets(int $week, float $baseWeight, float $step, float $mult = 1.0): array
    {
        $w = round(($baseWeight + ($week - 1) * $step) * $mult, 2);
        $warmupWeight = round($w * 0.5, 1);

        return [
            ['warmup' => true,  'reps' => 5, 'weight' => $warmupWeight, 'rir' => null],
            ['warmup' => false, 'reps' => 5, 'weight' => $w,            'rir' => 3],
            ['warmup' => false, 'reps' => 5, 'weight' => $w,            'rir' => 2],
            ['warmup' => false, 'reps' => 4, 'weight' => $w,            'rir' => 1],
        ];
    }

    /**
     * @param  array<int, array{exercise: Exercise, sets: list<array<string, mixed>>}>  $exercisePlan
     */
    private function historySession(
        MicrocycleWeek $week,
        string $name,
        int $order,
        Carbon $completedAt,
        array $exercisePlan,
        int $weekNum = 1
    ): void {
        $startedAt = $completedAt->copy()->subMinutes(65);

        $session = TrainingSession::create([
            'microcycle_week_id' => $week->id,
            'name' => $name,
            'order_in_week' => $order,
            'scheduled_date' => $completedAt->toDateString(),
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'status' => 'completed',
        ]);

        foreach ($exercisePlan as $position => $item) {
            /** @var Exercise $ex */
            $ex = $item['exercise'];
            $setsData = $item['sets'];

            $se = SessionExercise::create([
                'session_id' => $session->id,
                'group_id' => null,
                'exercise_id' => $ex->id,
                'order_in_session' => $position + 1,
                'order_in_group' => null,
                'technique_type' => 'straight',
                'planned_sets_count' => count($setsData),
                'planned_rest_sec' => 120,
            ]);

            foreach ($setsData as $idx => $setRow) {
                ExerciseSet::create([
                    'session_exercise_id' => $se->id,
                    'set_index' => $idx + 1,
                    'is_warmup' => $setRow['warmup'] ? 1 : 0,
                    'planned_reps' => $setRow['reps'],
                    'planned_weight_kg' => $setRow['weight'],
                    'planned_rir' => $setRow['warmup'] ? null : 2,
                    'actual_reps' => $setRow['reps'],
                    'actual_weight_kg' => $setRow['weight'],
                    'actual_rir' => $setRow['warmup'] ? null : $setRow['rir'],
                    'completed_at' => $startedAt->copy()->addMinutes(($position * 20) + ($idx * 4)),
                ]);
            }
        }

        // Feedback scala 0-3: settimane avanzate piu' intense, W4 deload piu' leggero
        $isDeload = ($weekNum === 4);
        SessionFeedback::create([
            'session_id' => $session->id,
            'pump' => $isDeload ? 1 : min(3, $weekNum),
            'soreness_prev' => $weekNum === 1 ? 0 : min(3, $weekNum - 1),
            'perceived_effort' => $isDeload ? 1 : min(3, $weekNum),
            'joint_pain' => $weekNum === 3 ? 1 : 0,
            'performance' => $isDeload ? 3 : max(1, 3 - ($weekNum - 1)),
            'sleep_hours' => 7.0 + ($order % 2 === 0 ? 0.5 : 0.0),
            'stress_level' => $weekNum === 3 ? 2 : 1,
            'note' => null,
            'created_at' => $completedAt,
        ]);
    }

    // -------------------------------------------------------------------------
    // Sezione 2 — Mesociclo attivo PPL (ex ActiveMesocycleSeeder)
    // -------------------------------------------------------------------------

    /** @param array<string, Exercise> $exercises */
    private function seedActiveMesocycles(Collection $athletes, Collection $trainers, array $exercises): void
    {
        foreach ($athletes as $i => $athlete) {
            Mesocycle::where('athlete_id', $athlete->id)
                ->where('name', self::MESO_NAME_ACTIVE)
                ->get()
                ->each(fn ($m) => $m->delete());

            DB::table('athlete_volume_landmarks')->where('athlete_id', $athlete->id)->delete();

            $trainer = $trainers[$i % $trainers->count()];
            $mult = self::WEIGHT_MULTIPLIERS[$i % count(self::WEIGHT_MULTIPLIERS)];

            DB::transaction(function () use ($athlete, $trainer, $exercises, $mult): void {
                $week1Start = Carbon::now()->startOfWeek()->subWeeks(1);

                $mesocycle = Mesocycle::create([
                    'athlete_id' => $athlete->id,
                    'trainer_id' => $trainer->id,
                    'template_id' => null,
                    'name' => self::MESO_NAME_ACTIVE,
                    'goal' => 'hypertrophy',
                    'periodization_model' => 'linear',
                    'start_date' => $week1Start->toDateString(),
                    'weeks_count' => 4,
                    'status' => 'active',
                ]);

                for ($weekNum = 1; $weekNum <= 4; $weekNum++) {
                    $weekStart = $week1Start->copy()->addWeeks($weekNum - 1);
                    $weekEnd = $weekStart->copy()->addDays(6);

                    $week = MicrocycleWeek::create([
                        'mesocycle_id' => $mesocycle->id,
                        'week_number' => $weekNum,
                        'is_deload' => ($weekNum === 4),
                        'start_date' => $weekStart->toDateString(),
                        'end_date' => $weekEnd->toDateString(),
                    ]);

                    $isPastWeek = $weekEnd->isPast();

                    $this->activeSessionPush($week, $weekNum, $weekStart->copy()->addDays(0), $isPastWeek, $exercises, $mult);
                    $this->activeSessionPull($week, $weekNum, $weekStart->copy()->addDays(2), $isPastWeek, $exercises, $mult);
                    $this->activeSessionLegs($week, $weekNum, $weekStart->copy()->addDays(4), $isPastWeek, $exercises, $mult);
                }
            });

            $this->seedVolumeLandmarks($athlete->id);

            $this->command->info("TrainingDemoSeeder: mesociclo attivo creato per {$athlete->email} (trainer: {$trainer->name})");
        }
    }

    private function activeSessionPush(
        MicrocycleWeek $week,
        int $weekNum,
        Carbon $date,
        bool $completed,
        array $exercises,
        float $mult
    ): void {
        $this->activeSession(
            week: $week,
            name: 'Push A',
            order: 1,
            date: $date,
            completed: $completed,
            exercisePlan: array_values(array_filter([
                isset($exercises['bench']) ? ['exercise' => $exercises['bench'],   'sets' => $this->straightSets($weekNum, 80.0, 2.5, 10, $mult)] : null,
                isset($exercises['incline']) ? ['exercise' => $exercises['incline'], 'sets' => $this->straightSets($weekNum, 60.0, 2.5, 10, $mult)] : null,
                isset($exercises['ohp']) ? ['exercise' => $exercises['ohp'],     'sets' => $this->straightSets($weekNum, 50.0, 2.5, 10, $mult)] : null,
                isset($exercises['lateral']) ? ['exercise' => $exercises['lateral'], 'sets' => $this->straightSets($weekNum, 14.0, 0.0, 15, $mult)] : null,
            ]))
        );
    }

    private function activeSessionPull(
        MicrocycleWeek $week,
        int $weekNum,
        Carbon $date,
        bool $completed,
        array $exercises,
        float $mult
    ): void {
        $this->activeSession(
            week: $week,
            name: 'Pull B',
            order: 2,
            date: $date,
            completed: $completed,
            exercisePlan: array_values(array_filter([
                isset($exercises['deadlift']) ? ['exercise' => $exercises['deadlift'], 'sets' => $this->straightSets($weekNum, 120.0, 5.0, 5, $mult)] : null,
                isset($exercises['row']) ? ['exercise' => $exercises['row'],      'sets' => $this->straightSets($weekNum, 70.0, 2.5, 8, $mult)] : null,
                isset($exercises['pullup']) ? ['exercise' => $exercises['pullup'],   'sets' => $this->bwSets($weekNum)] : null,
                isset($exercises['curl']) ? ['exercise' => $exercises['curl'],     'sets' => $this->straightSets($weekNum, 30.0, 1.25, 10, $mult)] : null,
            ]))
        );
    }

    private function activeSessionLegs(
        MicrocycleWeek $week,
        int $weekNum,
        Carbon $date,
        bool $completed,
        array $exercises,
        float $mult
    ): void {
        $this->activeSession(
            week: $week,
            name: 'Legs C',
            order: 3,
            date: $date,
            completed: $completed,
            exercisePlan: array_values(array_filter([
                isset($exercises['squat']) ? ['exercise' => $exercises['squat'],     'sets' => $this->straightSets($weekNum, 100.0, 2.5, 8, $mult)] : null,
                isset($exercises['leg_press']) ? ['exercise' => $exercises['leg_press'], 'sets' => $this->straightSets($weekNum, 160.0, 5.0, 12, $mult)] : null,
                isset($exercises['leg_curl']) ? ['exercise' => $exercises['leg_curl'],  'sets' => $this->straightSets($weekNum, 40.0, 2.5, 10, $mult)] : null,
            ]))
        );
    }

    /**
     * @param  array<int, array{exercise: Exercise, sets: list<array<string, mixed>>}>  $exercisePlan
     */
    private function activeSession(
        MicrocycleWeek $week,
        string $name,
        int $order,
        Carbon $date,
        bool $completed,
        array $exercisePlan
    ): void {
        if ($completed) {
            $completedAt = $date->copy()->setHour(19)->setMinute(0);
            $startedAt = $completedAt->copy()->subMinutes(70);

            $session = TrainingSession::create([
                'microcycle_week_id' => $week->id,
                'name' => $name,
                'order_in_week' => $order,
                'scheduled_date' => $date->toDateString(),
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
                'status' => 'completed',
            ]);

            foreach ($exercisePlan as $position => $item) {
                /** @var Exercise $ex */
                $ex = $item['exercise'];
                $setsData = $item['sets'];

                $se = SessionExercise::create([
                    'session_id' => $session->id,
                    'group_id' => null,
                    'exercise_id' => $ex->id,
                    'order_in_session' => $position + 1,
                    'order_in_group' => null,
                    'technique_type' => 'straight',
                    'planned_sets_count' => count($setsData),
                    'planned_rest_sec' => 120,
                ]);

                foreach ($setsData as $idx => $setRow) {
                    ExerciseSet::create([
                        'session_exercise_id' => $se->id,
                        'set_index' => $idx + 1,
                        'is_warmup' => $setRow['warmup'] ? 1 : 0,
                        'planned_reps' => $setRow['reps'],
                        'planned_weight_kg' => $setRow['weight'],
                        'planned_rir' => $setRow['warmup'] ? null : 2,
                        'actual_reps' => $setRow['reps'],
                        'actual_weight_kg' => $setRow['weight'],
                        'actual_rir' => $setRow['warmup'] ? null : $setRow['rir'],
                        'completed_at' => $startedAt->copy()->addMinutes(($position * 20) + ($idx * 4)),
                    ]);
                }
            }
        } else {
            $session = TrainingSession::create([
                'microcycle_week_id' => $week->id,
                'name' => $name,
                'order_in_week' => $order,
                'scheduled_date' => $date->toDateString(),
                'started_at' => null,
                'completed_at' => null,
                'status' => 'planned',
            ]);

            foreach ($exercisePlan as $position => $item) {
                /** @var Exercise $ex */
                $ex = $item['exercise'];
                $setsData = $item['sets'];

                $se = SessionExercise::create([
                    'session_id' => $session->id,
                    'group_id' => null,
                    'exercise_id' => $ex->id,
                    'order_in_session' => $position + 1,
                    'order_in_group' => null,
                    'technique_type' => 'straight',
                    'planned_sets_count' => count($setsData),
                    'planned_rest_sec' => 120,
                ]);

                foreach ($setsData as $idx => $setRow) {
                    ExerciseSet::create([
                        'session_exercise_id' => $se->id,
                        'set_index' => $idx + 1,
                        'is_warmup' => $setRow['warmup'] ? 1 : 0,
                        'planned_reps' => $setRow['reps'],
                        'planned_weight_kg' => $setRow['weight'],
                        'planned_rir' => $setRow['warmup'] ? null : 2,
                        'actual_reps' => null,
                        'actual_weight_kg' => null,
                        'actual_rir' => null,
                        'completed_at' => null,
                    ]);
                }
            }
        }
    }

    /**
     * Landmark personalizzati calibrati sui volumi reali del PPL demo (settimana 1, 3 working set).
     * Produce tutti e 5 gli stati visibili nella pagina dettaglio mesociclo:
     *   over_mrv       → latissimus_dorsi    (~3.45 set reali, MRV=3)
     *   approaching_mrv → hamstrings         (~4.8 set reali, MAV_MAX=5, MRV=7)
     *   in_mav         → deltoid_lateral     (~3.3 set reali, MAV_MIN=3, MAV_MAX=5)
     *   in_mav         → biceps_brachii      (~3.3 set reali, MAV_MIN=3, MAV_MAX=5)
     *   below_mev      → triceps_brachii     (~1.65 set reali, MEV=3)
     *   no_landmark    → brachioradialis     (~0.15 set reali, assente da config e da questa tabella)
     */
    private function seedVolumeLandmarks(int $athleteId): void
    {
        // [slug => [mev, mav_min, mav_max, mrv]]
        $landmarks = [
            'latissimus_dorsi' => [2, 2, 3, 3],
            'hamstrings' => [3, 4, 5, 7],
            'deltoid_lateral' => [2, 3, 5, 7],
            'biceps_brachii' => [2, 3, 5, 7],
            'triceps_brachii' => [3, 4, 6, 8],
            'deltoid_anterior' => [3, 4, 7, 9],
            'quadriceps' => [4, 5, 8, 10],
            'gluteus_maximus' => [2, 3, 5, 7],
            'pectoralis_major_sternal' => [3, 4, 6, 8],
            'pectoralis_major_clavicular' => [3, 4, 6, 8],
        ];

        $muscleIds = Muscle::whereIn('slug', array_keys($landmarks))
            ->pluck('id', 'slug');

        $now = now();
        $rows = [];
        foreach ($landmarks as $slug => [$mev, $mavMin, $mavMax, $mrv]) {
            $muscleId = $muscleIds[$slug] ?? null;
            if ($muscleId === null) {
                continue;
            }
            $rows[] = [
                'athlete_id' => $athleteId,
                'muscle_id' => $muscleId,
                'mev' => $mev,
                'mav_min' => $mavMin,
                'mav_max' => $mavMax,
                'mrv' => $mrv,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($rows)) {
            DB::table('athlete_volume_landmarks')->insert($rows);
        }
    }

    // -------------------------------------------------------------------------
    // Sezione 3 — Progressi atleta principale (ex ProgressDemoSeeder)
    // -------------------------------------------------------------------------

    /** @param array<string, Exercise> $exercises */
    private function seedProgress(array $exercises): void
    {
        $athlete = User::where('email', 'atleta@atleta.atleta')->first();
        $trainer = User::where('email', 'trainer@trainer.trainer')->first();

        if (! $athlete || ! $trainer) {
            $this->command->error('Atleta o trainer demo non trovati. Esegui prima CoreDemoSeeder.');

            return;
        }

        if (count($exercises) < 8) {
            $this->command->error('Esercizi insufficienti. Esegui prima ExerciseSeeder.');

            return;
        }

        // Pulizia idempotente
        Mesocycle::where('athlete_id', $athlete->id)
            ->whereIn('name', self::MESO_NAMES_PROGRESS)
            ->get()
            ->each(fn ($m) => $m->delete());

        BodyMeasurement::where('athlete_id', $athlete->id)
            ->where('measured_at', '>=', now()->subDays(90)->toDateString())
            ->delete();

        DB::transaction(function () use ($athlete, $trainer, $exercises): void {
            $this->seedBodyMeasurements($athlete->id);
            $this->seedTrainingPhases($athlete, $trainer, $exercises);
        });

        $this->command->info('TrainingDemoSeeder: 5 fasi PPL (20 settimane), misurazioni inserite per atleta@atleta.atleta');
    }

    private function seedBodyMeasurements(int $athleteId): void
    {
        // 15 misurazioni negli ultimi 90 giorni con progressione realistica
        $entries = [
            ['days_ago' => 88, 'weight' => 85.4],
            ['days_ago' => 82, 'weight' => 85.1],
            ['days_ago' => 75, 'weight' => 84.9],
            ['days_ago' => 69, 'weight' => 85.2],
            ['days_ago' => 63, 'weight' => 84.7],
            ['days_ago' => 56, 'weight' => 84.5],
            ['days_ago' => 49, 'weight' => 84.3],
            ['days_ago' => 42, 'weight' => 84.6],
            ['days_ago' => 35, 'weight' => 84.0],
            ['days_ago' => 28, 'weight' => 83.8],
            ['days_ago' => 21, 'weight' => 83.6],
            ['days_ago' => 14, 'weight' => 83.9],
            ['days_ago' => 7,  'weight' => 83.5],
            ['days_ago' => 3,  'weight' => 83.7],
            ['days_ago' => 1,  'weight' => 83.4],
        ];

        foreach ($entries as $entry) {
            BodyMeasurement::create([
                'athlete_id' => $athleteId,
                'measured_at' => now()->subDays($entry['days_ago'])->toDateString(),
                'weight_kg' => $entry['weight'],
            ]);
        }
    }

    /**
     * 5 mesocicli PPL completi distribuiti negli ultimi 6 mesi (copertura densa per il chart Volume).
     * Offsets scelti per coprire 24..5 settimane fa senza gap.
     */
    private function seedTrainingPhases(User $athlete, User $trainer, array $exercises): void
    {
        $phaseOffsets = [24, 20, 16, 12, 8]; // settimane fa dall'inizio di ciascuna fase

        foreach (self::MESO_NAMES_PROGRESS as $i => $name) {
            $startDate = Carbon::now()->subWeeks($phaseOffsets[$i])->startOfWeek();

            $mesocycle = Mesocycle::create([
                'athlete_id' => $athlete->id,
                'trainer_id' => $trainer->id,
                'template_id' => null,
                'name' => $name,
                'goal' => 'hypertrophy',
                'periodization_model' => 'linear',
                'start_date' => $startDate->toDateString(),
                'weeks_count' => 4,
                'status' => 'completed',
            ]);

            // Peso base cresce di fase in fase per simulare progressione forza
            $baseMultiplier = 1.0 + ($i * 0.04);

            for ($weekNum = 1; $weekNum <= 4; $weekNum++) {
                $weekStart = $startDate->copy()->addWeeks($weekNum - 1);
                $week = MicrocycleWeek::create([
                    'mesocycle_id' => $mesocycle->id,
                    'week_number' => $weekNum,
                    'is_deload' => ($weekNum === 4),
                    'start_date' => $weekStart->toDateString(),
                    'end_date' => $weekStart->copy()->addDays(6)->toDateString(),
                ]);

                $this->progressSessionPush($week, $weekNum, $weekStart->copy()->addDays(0), $exercises, $baseMultiplier);
                $this->progressSessionPull($week, $weekNum, $weekStart->copy()->addDays(2), $exercises, $baseMultiplier);
                $this->progressSessionLegs($week, $weekNum, $weekStart->copy()->addDays(4), $exercises, $baseMultiplier);
            }
        }
    }

    private function progressSessionPush(MicrocycleWeek $week, int $weekNum, Carbon $date, array $exercises, float $mult): void
    {
        $plan = [
            ['exercise' => $exercises['bench'],   'sets' => $this->straightSets($weekNum, round(80.0 * $mult, 1), 2.5, 10)],
            ['exercise' => $exercises['incline'], 'sets' => $this->straightSets($weekNum, round(60.0 * $mult, 1), 2.5, 10)],
            ['exercise' => $exercises['ohp'],     'sets' => $this->straightSets($weekNum, round(50.0 * $mult, 1), 2.5, 8)],
            ['exercise' => $exercises['lateral'], 'sets' => $this->straightSets($weekNum, round(14.0 * $mult, 1), 0.0, 15)],
        ];

        if (isset($exercises['crunch'])) {
            $plan[] = ['exercise' => $exercises['crunch'], 'sets' => $this->progressCoreSets($weekNum)];
        }

        $this->progressSession($week, 'Push A', 1, $date->copy()->setHour(18), $plan);
    }

    private function progressSessionPull(MicrocycleWeek $week, int $weekNum, Carbon $date, array $exercises, float $mult): void
    {
        $this->progressSession($week, 'Pull B', 2, $date->copy()->setHour(18), [
            ['exercise' => $exercises['deadlift'], 'sets' => $this->straightSets($weekNum, round(120.0 * $mult, 1), 5.0, 5)],
            ['exercise' => $exercises['row'],      'sets' => $this->straightSets($weekNum, round(70.0 * $mult, 1), 2.5, 8)],
            ['exercise' => $exercises['pullup'],   'sets' => $this->bwSets($weekNum)],
            ['exercise' => $exercises['curl'],     'sets' => $this->straightSets($weekNum, round(30.0 * $mult, 1), 1.25, 10)],
        ]);
    }

    private function progressSessionLegs(MicrocycleWeek $week, int $weekNum, Carbon $date, array $exercises, float $mult): void
    {
        $this->progressSession($week, 'Legs C', 3, $date->copy()->setHour(18), [
            ['exercise' => $exercises['squat'],     'sets' => $this->straightSets($weekNum, round(100.0 * $mult, 1), 2.5, 8)],
            ['exercise' => $exercises['leg_press'], 'sets' => $this->straightSets($weekNum, round(160.0 * $mult, 1), 5.0, 12)],
            ['exercise' => $exercises['leg_curl'],  'sets' => $this->straightSets($weekNum, round(40.0 * $mult, 1), 2.5, 10)],
        ]);
    }

    /** @param array<int, array{exercise: Exercise, sets: list<array<string, mixed>>}> $exercisePlan */
    private function progressSession(MicrocycleWeek $week, string $name, int $order, Carbon $completedAt, array $exercisePlan): void
    {
        $startedAt = $completedAt->copy()->subMinutes(70);

        $session = TrainingSession::create([
            'microcycle_week_id' => $week->id,
            'name' => $name,
            'order_in_week' => $order,
            'scheduled_date' => $completedAt->toDateString(),
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'status' => 'completed',
        ]);

        foreach ($exercisePlan as $position => $item) {
            /** @var Exercise $ex */
            $ex = $item['exercise'];
            $setsData = $item['sets'];

            $se = SessionExercise::create([
                'session_id' => $session->id,
                'group_id' => null,
                'exercise_id' => $ex->id,
                'order_in_session' => $position + 1,
                'order_in_group' => null,
                'technique_type' => 'straight',
                'planned_sets_count' => count($setsData),
                'planned_rest_sec' => 120,
            ]);

            foreach ($setsData as $idx => $setRow) {
                ExerciseSet::create([
                    'session_exercise_id' => $se->id,
                    'set_index' => $idx + 1,
                    'is_warmup' => $setRow['warmup'] ? 1 : 0,
                    'planned_reps' => $setRow['reps'],
                    'planned_weight_kg' => $setRow['weight'],
                    'planned_rir' => $setRow['warmup'] ? null : 2,
                    'actual_reps' => $setRow['reps'],
                    'actual_weight_kg' => $setRow['weight'],
                    'actual_rir' => $setRow['warmup'] ? null : $setRow['rir'],
                    'completed_at' => $startedAt->copy()->addMinutes(($position * 20) + ($idx * 4)),
                ]);
            }
        }
    }

    /** @return list<array<string, mixed>> */
    private function progressCoreSets(int $week): array
    {
        $reps = 15 + ($week - 1) * 3;

        return [
            ['warmup' => false, 'reps' => $reps, 'weight' => null, 'rir' => 2],
            ['warmup' => false, 'reps' => $reps, 'weight' => null, 'rir' => 1],
            ['warmup' => false, 'reps' => $reps, 'weight' => null, 'rir' => 1],
        ];
    }
}
