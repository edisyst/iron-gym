<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\ExerciseSet;
use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\SessionExercise;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Popola storico allenamenti di test per l'atleta demo.
 * Idempotente: elimina e ricrea i dati a ogni esecuzione.
 * Uso: php artisan db:seed --class=TrainingHistorySeeder
 */
class TrainingHistorySeeder extends Seeder
{
    private const MESO_NAME = '[TEST] Storico';

    public function run(): void
    {
        $athlete = User::where('email', 'atleta@atleta.atleta')->first();
        $trainer = User::where('email', 'trainer@trainer.trainer')->first();

        if (! $athlete || ! $trainer) {
            $this->command->error('Atleta o trainer demo non trovati. Esegui prima DemoSeeder.');

            return;
        }

        // Elimina dati precedenti per idempotenza
        Mesocycle::where('athlete_id', $athlete->id)
            ->where('name', self::MESO_NAME)
            ->get()
            ->each(fn ($m) => $m->delete());

        $exercises = $this->resolveExercises();

        if (count($exercises) < 6) {
            $this->command->error('Esercizi non trovati nel DB. Esegui prima ExerciseSeeder.');

            return;
        }

        DB::transaction(function () use ($athlete, $trainer, $exercises): void {
            $startDate = Carbon::now()->subWeeks(6)->startOfWeek();

            $mesocycle = Mesocycle::create([
                'athlete_id' => $athlete->id,
                'trainer_id' => $trainer->id,
                'template_id' => null,
                'name' => self::MESO_NAME,
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

                // Push — lunedi
                $pushAt = $weekStart->copy()->setHour(18)->setMinute(0);
                $this->seedSession($week, 'Push A', 1, $pushAt, [
                    ['exercise' => $exercises['bench'],    'sets' => $this->pushSets($weekNum, 80.0, 2.5)],
                    ['exercise' => $exercises['ohp'],      'sets' => $this->pushSets($weekNum, 50.0, 2.5)],
                    ['exercise' => $exercises['incline'],  'sets' => $this->pushSets($weekNum, 60.0, 2.5)],
                ]);

                // Pull — mercoledi
                $pullAt = $weekStart->copy()->addDays(2)->setHour(18)->setMinute(0);
                $this->seedSession($week, 'Pull B', 2, $pullAt, [
                    ['exercise' => $exercises['deadlift'], 'sets' => $this->pullSets($weekNum, 120.0, 5.0)],
                    ['exercise' => $exercises['pullup'],   'sets' => $this->bwSets($weekNum)],
                    ['exercise' => $exercises['curl'],     'sets' => $this->pushSets($weekNum, 30.0, 1.25)],
                ]);
            }
        });

        $this->command->info('TrainingHistorySeeder completato: 4 settimane × 2 sessioni per atleta@atleta.atleta');
    }

    /** @return array<string, Exercise> */
    private function resolveExercises(): array
    {
        $slugs = [
            'bench' => 'barbell_bench_press',
            'ohp' => 'overhead_press_standing',
            'incline' => 'incline_barbell_bench_press',
            'deadlift' => 'conventional_deadlift',
            'pullup' => 'pull_up_pronated',
            'curl' => 'barbell_curl',
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
     * @param  array<int, array{exercise: Exercise, sets: list<array<string, mixed>>}>  $exercisePlan
     */
    private function seedSession(
        MicrocycleWeek $week,
        string $name,
        int $order,
        Carbon $completedAt,
        array $exercisePlan
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
    }

    /**
     * 1 warmup + 3 working set. Peso cresce +$step per settimana.
     *
     * @return list<array<string, mixed>>
     */
    private function pushSets(int $week, float $baseWeight, float $step): array
    {
        $w = round($baseWeight + ($week - 1) * $step, 2);
        $warmupWeight = round($w * 0.6, 1);

        return [
            ['warmup' => true,  'reps' => 10, 'weight' => $warmupWeight, 'rir' => null],
            ['warmup' => false, 'reps' => 10, 'weight' => $w,            'rir' => 3],
            ['warmup' => false, 'reps' => 9,  'weight' => $w,            'rir' => 2],
            ['warmup' => false, 'reps' => 8,  'weight' => $w,            'rir' => 1],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pullSets(int $week, float $baseWeight, float $step): array
    {
        $w = round($baseWeight + ($week - 1) * $step, 2);
        $warmupWeight = round($w * 0.5, 1);

        return [
            ['warmup' => true,  'reps' => 5,  'weight' => $warmupWeight, 'rir' => null],
            ['warmup' => false, 'reps' => 5,  'weight' => $w,            'rir' => 3],
            ['warmup' => false, 'reps' => 5,  'weight' => $w,            'rir' => 2],
            ['warmup' => false, 'reps' => 4,  'weight' => $w,            'rir' => 1],
        ];
    }

    /**
     * Trazioni a corpo libero — rep cresce +1 per settimana.
     *
     * @return list<array<string, mixed>>
     */
    private function bwSets(int $week): array
    {
        $reps = 6 + ($week - 1);

        return [
            ['warmup' => false, 'reps' => $reps,     'weight' => null, 'rir' => 3],
            ['warmup' => false, 'reps' => $reps,     'weight' => null, 'rir' => 2],
            ['warmup' => false, 'reps' => $reps - 1, 'weight' => null, 'rir' => 1],
        ];
    }
}
