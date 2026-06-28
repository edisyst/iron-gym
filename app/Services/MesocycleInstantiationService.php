<?php

namespace App\Services;

use App\Models\ExerciseSet;
use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\SessionExercise;
use App\Models\SessionExerciseGroup;
use App\Models\TemplateSessionExercise;
use App\Models\TrainingSession;
use App\Models\WorkoutTemplate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MesocycleInstantiationService
{
    /**
     * Istanzia un mesociclo da un template per un atleta.
     * Crea in cascade: MicrocycleWeek, TrainingSession, SessionExercise (con gruppi), ExerciseSet.
     * Tutta l'operazione è avvolta in una transaction: rollback automatico su eccezione.
     *
     * @param  array{name: string, goal: string, periodization_model: string, start_date: string|Carbon, weeks_count: int, deload_last_week?: bool}  $params
     */
    public function instantiate(WorkoutTemplate $template, int $athleteId, int $trainerId, array $params): Mesocycle
    {
        return DB::transaction(function () use ($template, $athleteId, $trainerId, $params): Mesocycle {

            $startDate = Carbon::parse($params['start_date'])->startOfDay();

            // 1. Crea il mesociclo
            $mesocycle = Mesocycle::create([
                'athlete_id' => $athleteId,
                'trainer_id' => $trainerId,
                'template_id' => $template->id,
                'name' => $params['name'],
                'goal' => $params['goal'],
                'periodization_model' => $params['periodization_model'],
                'start_date' => $startDate,
                'weeks_count' => $params['weeks_count'],
                'status' => 'active',
            ]);

            // 2. Crea le MicrocycleWeek
            $weeks = [];
            for ($weekNum = 1; $weekNum <= $params['weeks_count']; $weekNum++) {
                $weekStart = $startDate->copy()->addDays(($weekNum - 1) * 7);
                $weekEnd = $weekStart->copy()->addDays(6);

                $week = MicrocycleWeek::create([
                    'mesocycle_id' => $mesocycle->id,
                    'week_number' => $weekNum,
                    'is_deload' => ($weekNum === (int) $params['weeks_count']) && ($params['deload_last_week'] ?? true),
                    'start_date' => $weekStart,
                    'end_date' => $weekEnd,
                ]);

                // Indicizza le settimane per week_number per accesso O(1)
                $weeks[$weekNum] = $week;
            }

            // Carica tutte le template sessions con i loro esercizi
            $template->load(['templateSessions.templateExercises']);

            // 3. Per ogni template session crea TrainingSession + SessionExercise + ExerciseSet
            foreach ($template->templateSessions as $templateSession) {
                /** @var MicrocycleWeek|null $week */
                $week = $weeks[$templateSession->week_number] ?? null;

                if ($week === null) {
                    // week_number fuori range rispetto a weeks_count: salta silenziosamente
                    continue;
                }

                // La data programmata è il primo giorno della settimana + (order_in_week - 1) giorni
                $scheduledDate = $week->start_date->copy()->addDays($templateSession->order_in_week - 1);

                $session = TrainingSession::create([
                    'microcycle_week_id' => $week->id,
                    'name' => $templateSession->name,
                    'order_in_week' => $templateSession->order_in_week,
                    'scheduled_date' => $scheduledDate,
                    'status' => 'planned',
                ]);

                // 4. Gestisci i gruppi (superset / giant_set) e gli esercizi standalone
                // Raccogli gli esercizi con group_key non null e raggruppa per group_key
                /** @var Collection<int, TemplateSessionExercise> $templateExercises */
                $templateExercises = $templateSession->templateExercises->sortBy('order_in_session');

                // Mappa group_key => SessionExerciseGroup id (creato al volo al primo esercizio del gruppo)
                $groupMap = [];

                foreach ($templateExercises as $tse) {
                    $groupId = null;
                    $orderInGroup = null;

                    if ($tse->group_key !== null) {
                        if (! isset($groupMap[$tse->group_key])) {
                            // Primo esercizio di questo gruppo: calcola order_in_session del gruppo
                            // come order_in_session del primo esercizio del gruppo
                            $firstInGroup = $templateExercises
                                ->where('group_key', $tse->group_key)
                                ->first();

                            $group = SessionExerciseGroup::create([
                                'session_id' => $session->id,
                                'group_type' => $tse->group_type ?? 'superset',
                                'order_in_session' => $firstInGroup->order_in_session,
                                'rounds' => 3,
                                'rest_between_rounds_sec' => null,
                            ]);

                            $groupMap[$tse->group_key] = $group->id;
                        }

                        $groupId = $groupMap[$tse->group_key];

                        // Calcola order_in_group come posizione relativa dentro il gruppo (1-based)
                        $orderInGroup = $templateExercises
                            ->where('group_key', $tse->group_key)
                            ->values()
                            ->search(fn ($item) => $item->id === $tse->id) + 1;
                    }

                    $sessionExercise = SessionExercise::create([
                        'session_id' => $session->id,
                        'group_id' => $groupId,
                        'exercise_id' => $tse->exercise_id,
                        'order_in_session' => $tse->order_in_session,
                        'order_in_group' => $orderInGroup,
                        'technique_type' => $tse->technique_type,
                        'tempo' => $tse->tempo,
                        'planned_sets_count' => $tse->planned_sets_count,
                        'planned_rest_sec' => $tse->planned_rest_sec,
                        'trainer_note' => $tse->note,
                    ]);

                    // 5. Crea i set prescritti
                    for ($setIndex = 1; $setIndex <= $tse->planned_sets_count; $setIndex++) {
                        ExerciseSet::create([
                            'session_exercise_id' => $sessionExercise->id,
                            'set_index' => $setIndex,
                            'is_warmup' => false,
                            'planned_reps' => $tse->planned_reps,
                            'planned_weight_kg' => null,
                            'planned_rir' => $tse->planned_rir,
                        ]);
                    }
                }
            }

            return $mesocycle;
        });
    }
}
