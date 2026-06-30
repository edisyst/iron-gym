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
 * Crea un mesociclo PPL attivo per ogni atleta, con settimane passate completate
 * e sessioni della settimana corrente in stato planned.
 * Idempotente: elimina e ricrea il mesociclo demo a ogni esecuzione.
 */
class ActiveMesocycleSeeder extends Seeder
{
    private const MESO_NAME = '[DEMO] PPL Ipertrofia';

    private const WEIGHT_MULTIPLIERS = [1.0, 0.85, 1.1, 0.75, 0.95, 1.05];

    public function run(): void
    {
        $athletes = User::role('atleta')->orderBy('id')->get();
        $trainers = User::role('trainer')->orderBy('id')->get();

        if ($athletes->isEmpty()) {
            $this->command->error('Nessun atleta trovato. Esegui prima DemoSeeder.');

            return;
        }

        if ($trainers->isEmpty()) {
            $this->command->error('Nessun trainer trovato. Esegui prima DemoSeeder.');

            return;
        }

        $exercises = $this->resolveExercises();

        if (count($exercises) < 8) {
            $this->command->error('Esercizi insufficienti. Esegui prima ExerciseSeeder.');

            return;
        }

        foreach ($athletes as $i => $athlete) {
            Mesocycle::where('athlete_id', $athlete->id)
                ->where('name', self::MESO_NAME)
                ->get()
                ->each(fn ($m) => $m->delete());

            $trainer = $trainers[$i % $trainers->count()];
            $mult = self::WEIGHT_MULTIPLIERS[$i % count(self::WEIGHT_MULTIPLIERS)];

            DB::transaction(function () use ($athlete, $trainer, $exercises, $mult): void {
                $week1Start = Carbon::now()->startOfWeek()->subWeeks(1);

                $mesocycle = Mesocycle::create([
                    'athlete_id' => $athlete->id,
                    'trainer_id' => $trainer->id,
                    'template_id' => null,
                    'name' => self::MESO_NAME,
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

                    $this->seedPushSession($week, $weekNum, $weekStart->copy()->addDays(0), $isPastWeek, $exercises, $mult);
                    $this->seedPullSession($week, $weekNum, $weekStart->copy()->addDays(2), $isPastWeek, $exercises, $mult);
                    $this->seedLegsSession($week, $weekNum, $weekStart->copy()->addDays(4), $isPastWeek, $exercises, $mult);
                }
            });

            $this->command->info("ActiveMesocycleSeeder: mesociclo PPL attivo creato per {$athlete->email} (trainer: {$trainer->name})");
        }
    }

    /** @return array<string, Exercise> */
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

    private function seedPushSession(
        MicrocycleWeek $week,
        int $weekNum,
        Carbon $date,
        bool $completed,
        array $exercises,
        float $mult
    ): void {
        $this->seedSession(
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

    private function seedPullSession(
        MicrocycleWeek $week,
        int $weekNum,
        Carbon $date,
        bool $completed,
        array $exercises,
        float $mult
    ): void {
        $this->seedSession(
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

    private function seedLegsSession(
        MicrocycleWeek $week,
        int $weekNum,
        Carbon $date,
        bool $completed,
        array $exercises,
        float $mult
    ): void {
        $this->seedSession(
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
    private function seedSession(
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

    /** @return list<array<string, mixed>> */
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
}
