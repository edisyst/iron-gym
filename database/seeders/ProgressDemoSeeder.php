<?php

namespace Database\Seeders;

use App\Models\BodyMeasurement;
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
 * Popola dati demo per la pagina Progressi atleta (tutte e tre le tab).
 * Idempotente: elimina e ricrea i dati a ogni esecuzione.
 */
class ProgressDemoSeeder extends Seeder
{
    private const MESO_NAMES = [
        '[DEMO] PPL Fase 1',
        '[DEMO] PPL Fase 2',
        '[DEMO] PPL Fase 3',
        '[DEMO] PPL Fase 4',
        '[DEMO] PPL Fase 5',
    ];

    public function run(): void
    {
        $athlete = User::where('email', 'atleta@atleta.atleta')->first();
        $trainer = User::where('email', 'trainer@trainer.trainer')->first();

        if (! $athlete || ! $trainer) {
            $this->command->error('Atleta o trainer demo non trovati. Esegui prima DemoSeeder.');

            return;
        }

        $exercises = $this->resolveExercises();

        if (count($exercises) < 8) {
            $this->command->error('Esercizi insufficienti. Esegui prima ExerciseSeeder.');

            return;
        }

        // Pulizia idempotente
        Mesocycle::where('athlete_id', $athlete->id)
            ->whereIn('name', self::MESO_NAMES)
            ->get()
            ->each(fn ($m) => $m->delete());

        BodyMeasurement::where('athlete_id', $athlete->id)
            ->where('measured_at', '>=', now()->subDays(90)->toDateString())
            ->delete();

        DB::transaction(function () use ($athlete, $trainer, $exercises): void {
            $this->seedBodyMeasurements($athlete->id);
            $this->seedTrainingPhases($athlete, $trainer, $exercises);
        });

        $this->command->info('ProgressDemoSeeder: 5 fasi PPL (20 settimane), misurazioni peso e core inseriti per atleta@atleta.atleta');
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
            ['days_ago' =>  7, 'weight' => 83.5],
            ['days_ago' =>  3, 'weight' => 83.7],
            ['days_ago' =>  1, 'weight' => 83.4],
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
     * Ogni fase copre 4 settimane con sessioni Push + Pull + Legs + Abs.
     * Offsets scelti per coprire 24..5 settimane fa senza gap.
     */
    private function seedTrainingPhases(User $athlete, User $trainer, array $exercises): void
    {
        $phaseOffsets = [24, 20, 16, 12, 8]; // settimane fa dall'inizio di ciascuna fase

        foreach (self::MESO_NAMES as $i => $name) {
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

                $this->seedPushSession($week, $weekNum, $weekStart->copy()->addDays(0), $exercises, $baseMultiplier);
                $this->seedPullSession($week, $weekNum, $weekStart->copy()->addDays(2), $exercises, $baseMultiplier);
                $this->seedLegsSession($week, $weekNum, $weekStart->copy()->addDays(4), $exercises, $baseMultiplier);
            }
        }
    }

    private function seedPushSession(MicrocycleWeek $week, int $weekNum, Carbon $date, array $exercises, float $mult): void
    {
        $plan = [
            ['exercise' => $exercises['bench'],   'sets' => $this->workSets($weekNum, round(80.0 * $mult, 1), 2.5, 10)],
            ['exercise' => $exercises['incline'], 'sets' => $this->workSets($weekNum, round(60.0 * $mult, 1), 2.5, 10)],
            ['exercise' => $exercises['ohp'],     'sets' => $this->workSets($weekNum, round(50.0 * $mult, 1), 2.5, 8)],
            ['exercise' => $exercises['lateral'], 'sets' => $this->workSets($weekNum, round(14.0 * $mult, 1), 0.0, 15)],
        ];

        if (isset($exercises['crunch'])) {
            $plan[] = ['exercise' => $exercises['crunch'], 'sets' => $this->coreBodyweightSets($weekNum)];
        }

        $this->seedSession($week, 'Push A', 1, $date->copy()->setHour(18), $plan);
    }

    private function seedPullSession(MicrocycleWeek $week, int $weekNum, Carbon $date, array $exercises, float $mult): void
    {
        $this->seedSession($week, 'Pull B', 2, $date->copy()->setHour(18), [
            ['exercise' => $exercises['deadlift'], 'sets' => $this->workSets($weekNum, round(120.0 * $mult, 1), 5.0, 5)],
            ['exercise' => $exercises['row'],      'sets' => $this->workSets($weekNum, round(70.0 * $mult, 1), 2.5, 8)],
            ['exercise' => $exercises['pullup'],   'sets' => $this->bwSets($weekNum)],
            ['exercise' => $exercises['curl'],     'sets' => $this->workSets($weekNum, round(30.0 * $mult, 1), 1.25, 10)],
        ]);
    }

    private function seedLegsSession(MicrocycleWeek $week, int $weekNum, Carbon $date, array $exercises, float $mult): void
    {
        $this->seedSession($week, 'Legs C', 3, $date->copy()->setHour(18), [
            ['exercise' => $exercises['squat'],     'sets' => $this->workSets($weekNum, round(100.0 * $mult, 1), 2.5, 8)],
            ['exercise' => $exercises['leg_press'], 'sets' => $this->workSets($weekNum, round(160.0 * $mult, 1), 5.0, 12)],
            ['exercise' => $exercises['leg_curl'],  'sets' => $this->workSets($weekNum, round(40.0 * $mult, 1), 2.5, 10)],
        ]);
    }

    /** @param array<int, array{exercise: Exercise, sets: list<array<string, mixed>>}> $exercisePlan */
    private function seedSession(MicrocycleWeek $week, string $name, int $order, Carbon $completedAt, array $exercisePlan): void
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
    private function workSets(int $week, float $baseWeight, float $step, int $reps): array
    {
        $w = round($baseWeight + ($week - 1) * $step, 2);
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

    /** @return list<array<string, mixed>> */
    private function coreBodyweightSets(int $week): array
    {
        $reps = 15 + ($week - 1) * 3;

        return [
            ['warmup' => false, 'reps' => $reps, 'weight' => null, 'rir' => 2],
            ['warmup' => false, 'reps' => $reps, 'weight' => null, 'rir' => 1],
            ['warmup' => false, 'reps' => $reps, 'weight' => null, 'rir' => 1],
        ];
    }

    /** @return array<string, Exercise> */
    private function resolveExercises(): array
    {
        $slugs = [
            'bench'     => 'barbell_bench_press',
            'incline'   => 'incline_barbell_bench_press',
            'ohp'       => 'overhead_press_standing',
            'lateral'   => 'dumbbell_lateral_raise',
            'deadlift'  => 'conventional_deadlift',
            'row'       => 'barbell_row',
            'pullup'    => 'pull_up_pronated',
            'curl'      => 'barbell_curl',
            'squat'     => 'back_squat_high_bar',
            'leg_press' => 'leg_press_45',
            'leg_curl'  => 'lying_leg_curl',
            'crunch'    => 'floor_crunch',
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
}
