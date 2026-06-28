<?php

namespace Database\Seeders;

use App\Models\TemplateSession;
use App\Models\TemplateSessionExercise;
use App\Models\User;
use App\Models\WorkoutTemplate;
use Illuminate\Database\Seeder;

/**
 * Template pilota PPL ipertrofia — intermediato, 4 settimane, 3 sessioni/sett.
 * Volume progressivo: MEV settimana 1 → MRV settimana 3 → deload settimana 4.
 * Idempotente: elimina e ricrea il template se già esiste con lo stesso nome.
 */
class PilotTemplateSeeder extends Seeder
{
    private const TEMPLATE_NAME = 'PPL Ipertrofia — Intermediato (4 sett.)';

    // Esercizi per sessione: [exercise_id, planned_reps, planned_rir, planned_rest_sec]
    private const PUSH_EXERCISES = [
        [1,  8,  2, 180], // Panca piana bilanciere
        [4,  10, 2, 150], // Panca inclinata manubri
        [26, 8,  2, 150], // Military press OHP
        [29, 15, 2, 90],  // Alzate laterali manubri
        [45, 12, 2, 90],  // Push down cavi sbarra
    ];

    private const PULL_EXERCISES = [
        [16, 8,  2, 180], // Lat machine avanti
        [17, 10, 2, 150], // Pulley basso
        [34, 15, 1, 90],  // Alzate posteriori cavi
        [36, 10, 2, 90],  // Curl bilanciere
        [39, 12, 2, 90],  // Hammer curl
    ];

    private const LEGS_EXERCISES = [
        [53, 6,  2, 180], // Squat high-bar bilanciere
        [56, 12, 2, 150], // Leg press 45°
        [13, 10, 2, 150], // Stacco rumeno RDL
        [59, 15, 1, 90],  // Leg extension
        [60, 12, 2, 90],  // Leg curl sdraiato
        [71, 20, 1, 60],  // Calf raise in piedi
    ];

    // Set per settimana: [push_compound, push_iso, pull_compound, pull_iso, legs_compound, legs_iso]
    // Indicizzato: 0=panca/ohp, 1=alzate/tricipiti, 2=lat/pulley, 3=posteriori/curl, 4=squat/leg press/rdl, 5=leg ext/curl/calf
    private const WEEKLY_SETS = [
        1 => [3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 4],
        2 => [4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 3, 3, 4],
        3 => [4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4],
        4 => [2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2], // deload
    ];

    public function run(): void
    {
        WorkoutTemplate::where('name', self::TEMPLATE_NAME)->delete();

        $trainer = User::role('trainer')->first();

        $template = WorkoutTemplate::create([
            'name' => self::TEMPLATE_NAME,
            'description' => 'Scheda Push/Pull/Legs per atleti intermedi. 3 sessioni settimanali, volume progressivo dalle 3 alle 4 serie per esercizio, deload automatico alla settimana 4.',
            'goal' => 'hypertrophy',
            'periodization_model' => 'linear',
            'weeks_count' => 4,
            'days_per_week' => 3,
            'created_by' => $trainer?->id,
            'is_active' => true,
        ]);

        for ($week = 1; $week <= 4; $week++) {
            $this->createSession($template->id, $week, 1, 'Push — Petto / Spalle / Tricipiti', self::PUSH_EXERCISES, $week);
            $this->createSession($template->id, $week, 2, 'Pull — Schiena / Bicipiti', self::PULL_EXERCISES, $week);
            $this->createSession($template->id, $week, 3, 'Legs — Gambe / Glutei / Polpacci', self::LEGS_EXERCISES, $week);
        }

        $sessions = 4 * 3;
        $exercises = $sessions * 5 + 4 * 6; // Legs ha 6 esercizi, Push/Pull ne hanno 5

        $this->command->info("Template '{$template->name}' creato: {$sessions} sessioni, esercizi distribuiti su 4 settimane.");
    }

    /** @param array<int, array{0:int,1:int,2:int,3:int}> $exercises */
    private function createSession(int $templateId, int $weekNumber, int $orderInWeek, string $name, array $exercises, int $week): void
    {
        $session = TemplateSession::create([
            'template_id' => $templateId,
            'week_number' => $weekNumber,
            'name' => $name,
            'order_in_week' => $orderInWeek,
        ]);

        $sets = $week <= 3 ? ($week === 1 ? 3 : ($week === 2 ? 4 : 4)) : 2;
        $setsIso = $week <= 3 ? ($week === 3 ? 4 : 3) : 2;

        foreach ($exercises as $i => [$exerciseId, $reps, $rir, $rest]) {
            $isCompound = in_array($exerciseId, [1, 4, 26, 16, 17, 53, 56, 13], true);
            $setSets = $isCompound ? $sets : $setsIso;

            TemplateSessionExercise::create([
                'template_session_id' => $session->id,
                'exercise_id' => $exerciseId,
                'order_in_session' => $i + 1,
                'technique_type' => 'straight',
                'planned_sets_count' => $setSets,
                'planned_reps' => $reps,
                'planned_rir' => $week === 4 ? $rir + 1 : $rir,
                'planned_rest_sec' => $rest,
                'note' => null,
                'group_key' => null,
                'group_type' => null,
                'tempo' => null,
            ]);
        }
    }
}
