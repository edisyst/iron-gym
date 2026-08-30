<?php

namespace Database\Seeders;

use App\Models\TemplateSession;
use App\Models\TemplateSessionExercise;
use App\Models\User;
use App\Models\WorkoutTemplate;
use Illuminate\Database\Seeder;

/**
 * Template di allenamento: PPL pilota + 4 template dimostrativi.
 * Idempotente: elimina e ricrea i template con lo stesso nome.
 */
class TemplateSeeder extends Seeder
{
    private const PPL_NAME = 'PPL Ipertrofia — Intermediato (4 sett.)';

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

    public function run(): void
    {
        $trainer = User::role('trainer')->first();
        $creatorId = $trainer?->id;

        $this->createPplTemplate($creatorId);
        $this->createStrengthTemplate($creatorId);
        $this->createFullBodyTemplate($creatorId);
        $this->createBeginnerTemplate($creatorId);
        $this->createCutTemplate($creatorId);
    }

    // -------------------------------------------------------------------------
    // PPL pilota — volume progressivo MEV→MRV, deload settimana 4
    // -------------------------------------------------------------------------

    private function createPplTemplate(?int $creatorId): void
    {
        WorkoutTemplate::where('name', self::PPL_NAME)->delete();

        $template = WorkoutTemplate::create([
            'name' => self::PPL_NAME,
            'description' => 'Scheda Push/Pull/Legs per atleti intermedi. 3 sessioni settimanali, volume progressivo dalle 3 alle 4 serie per esercizio, deload automatico alla settimana 4.',
            'goal' => 'hypertrophy',
            'periodization_model' => 'linear',
            'weeks_count' => 4,
            'days_per_week' => 3,
            'created_by' => $creatorId,
            'is_active' => true,
        ]);

        for ($week = 1; $week <= 4; $week++) {
            $this->createPilotSession($template->id, $week, 1, 'Push — Petto / Spalle / Tricipiti', self::PUSH_EXERCISES, $week);
            $this->createPilotSession($template->id, $week, 2, 'Pull — Schiena / Bicipiti', self::PULL_EXERCISES, $week);
            $this->createPilotSession($template->id, $week, 3, 'Legs — Gambe / Glutei / Polpacci', self::LEGS_EXERCISES, $week);
        }

        $sessions = 4 * 3;
        $this->command->info("Template '".self::PPL_NAME."' creato: {$sessions} sessioni.");
    }

    /** @param array<int, array{0:int,1:int,2:int,3:int}> $exercises */
    private function createPilotSession(int $templateId, int $weekNumber, int $orderInWeek, string $name, array $exercises, int $week): void
    {
        $session = TemplateSession::create([
            'template_id' => $templateId,
            'week_number' => $weekNumber,
            'name' => $name,
            'order_in_week' => $orderInWeek,
        ]);

        // Compound: 3 serie W1, 4 W2-W3, 2 W4 deload. Iso: 3 W1-W2, 4 W3, 2 W4.
        $sets = $week <= 3 ? ($week === 1 ? 3 : 4) : 2;
        $setsIso = $week <= 3 ? ($week === 3 ? 4 : 3) : 2;

        foreach ($exercises as $i => [$exerciseId, $reps, $rir, $rest]) {
            $isCompound = in_array($exerciseId, [1, 4, 26, 16, 17, 53, 56, 13], true);
            TemplateSessionExercise::create([
                'template_session_id' => $session->id,
                'exercise_id' => $exerciseId,
                'order_in_session' => $i + 1,
                'technique_type' => 'straight',
                'planned_sets_count' => $isCompound ? $sets : $setsIso,
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

    // -------------------------------------------------------------------------
    // Template dimostrativi
    // -------------------------------------------------------------------------

    private function createStrengthTemplate(?int $creatorId): void
    {
        $name = 'Forza Powerlifting — 5x5 (6 sett.)';
        WorkoutTemplate::where('name', $name)->delete();

        $template = WorkoutTemplate::create([
            'name' => $name,
            'description' => 'Programma 5x5 per sviluppo forza massimale. Focus sui tre grandi (squat, panca, stacco). Progressione lineare del carico ogni sessione.',
            'goal' => 'strength',
            'periodization_model' => 'linear',
            'weeks_count' => 6,
            'days_per_week' => 3,
            'created_by' => $creatorId,
            'is_active' => true,
        ]);

        // Sessione A: Squat + Panca + Rematore
        $sessionAExercises = [
            [53, 5, 3, 240], // Squat high-bar
            [1,  5, 3, 240], // Panca piana bilanciere
            [20, 5, 3, 180], // Rematore bilanciere
        ];

        // Sessione B: Squat + OHP + Stacco
        $sessionBExercises = [
            [53, 5, 3, 240], // Squat high-bar
            [26, 5, 3, 240], // Military press OHP
            [10, 5, 3, 300], // Stacco da terra
        ];

        for ($week = 1; $week <= 6; $week++) {
            $this->buildSession($template->id, $week, 1, "Sessione A — Sett. {$week}", $sessionAExercises, 5);
            $this->buildSession($template->id, $week, 2, "Sessione B — Sett. {$week}", $sessionBExercises, 5);
            $this->buildSession($template->id, $week, 3, "Sessione A2 — Sett. {$week}", $sessionAExercises, 5);
        }

        $this->command->info("Template '{$name}' creato.");
    }

    private function createFullBodyTemplate(?int $creatorId): void
    {
        $name = 'Full Body — Ipertrofia Intermedio (4 sett.)';
        WorkoutTemplate::where('name', $name)->delete();

        $template = WorkoutTemplate::create([
            'name' => $name,
            'description' => 'Full body 3 volte a settimana con frequenza alta per ogni muscolo. Alternanza di esercizi compound e isolamento. Adatto a chi ha 2+ anni di esperienza.',
            'goal' => 'hypertrophy',
            'periodization_model' => 'undulating_dup',
            'weeks_count' => 4,
            'days_per_week' => 3,
            'created_by' => $creatorId,
            'is_active' => true,
        ]);

        // Lunedì: orientato forza (rep basse)
        $mondayExercises = [
            [53, 6,  2, 210], // Squat
            [1,  6,  2, 210], // Panca piana
            [20, 6,  2, 180], // Rematore bilanciere
            [26, 6,  2, 180], // OHP
        ];

        // Mercoledì: orientato volume (rep medie)
        $wednesdayExercises = [
            [56, 10, 2, 150], // Leg press
            [4,  10, 2, 150], // Panca inclinata
            [16, 10, 2, 150], // Lat machine
            [29, 12, 2, 90],  // Alzate laterali
            [45, 12, 2, 90],  // Push down cavi
        ];

        // Venerdì: orientato pump (rep alte)
        $fridayExercises = [
            [59, 15, 1, 90],  // Leg extension
            [60, 12, 2, 90],  // Leg curl
            [6,  12, 2, 90],  // Croci cavi crossover
            [17, 12, 2, 90],  // Pulley basso
            [36, 12, 2, 90],  // Curl bilanciere
            [71, 20, 1, 60],  // Calf raise
        ];

        for ($week = 1; $week <= 4; $week++) {
            $sets = $week === 4 ? 2 : ($week === 1 ? 3 : 4);
            $this->buildSession($template->id, $week, 1, "Lunedì Forza — Sett. {$week}", $mondayExercises, $sets);
            $this->buildSession($template->id, $week, 2, "Mercoledì Volume — Sett. {$week}", $wednesdayExercises, $sets);
            $this->buildSession($template->id, $week, 3, "Venerdì Pump — Sett. {$week}", $fridayExercises, $sets);
        }

        $this->command->info("Template '{$name}' creato.");
    }

    private function createBeginnerTemplate(?int $creatorId): void
    {
        $name = 'Principiante — Total Body (8 sett.)';
        WorkoutTemplate::where('name', $name)->delete();

        $template = WorkoutTemplate::create([
            'name' => $name,
            'description' => 'Programma per chi inizia. Movimenti fondamentali con carichi moderati, apprendimento della tecnica e adattamento neuromuscolare. 2-3 sessioni settimanali.',
            'goal' => 'general',
            'periodization_model' => 'linear',
            'weeks_count' => 8,
            'days_per_week' => 3,
            'created_by' => $creatorId,
            'is_active' => false,
        ]);

        $exercises = [
            [53, 8,  3, 180], // Squat
            [1,  8,  3, 180], // Panca piana
            [16, 8,  3, 180], // Lat machine
            [26, 10, 3, 150], // OHP
            [59, 12, 2, 90],  // Leg extension
            [36, 12, 2, 90],  // Curl bilanciere
        ];

        for ($week = 1; $week <= 8; $week++) {
            $sets = $week <= 2 ? 2 : ($week <= 6 ? 3 : ($week <= 7 ? 4 : 2));
            $this->buildSession($template->id, $week, 1, "Sessione A — Sett. {$week}", $exercises, $sets);
            $this->buildSession($template->id, $week, 2, "Sessione B — Sett. {$week}", $exercises, $sets);
            $this->buildSession($template->id, $week, 3, "Sessione A2 — Sett. {$week}", $exercises, $sets);
        }

        $this->command->info("Template '{$name}' creato.");
    }

    private function createCutTemplate(?int $creatorId): void
    {
        $name = 'Cut — Mantenimento Massa (6 sett.)';
        WorkoutTemplate::where('name', $name)->delete();

        $template = WorkoutTemplate::create([
            'name' => $name,
            'description' => 'Scheda per fase di definizione. Volume ridotto rispetto all\'ipertrofia, intensità mantenuta alta per preservare la massa muscolare durante il deficit calorico.',
            'goal' => 'cut',
            'periodization_model' => 'block',
            'weeks_count' => 6,
            'days_per_week' => 4,
            'created_by' => $creatorId,
            'is_active' => true,
        ]);

        $upperExercises = [
            [1,  6,  2, 210], // Panca piana
            [16, 8,  2, 180], // Lat machine
            [26, 8,  2, 180], // OHP
            [17, 10, 2, 120], // Pulley basso
            [29, 15, 1, 60],  // Alzate laterali
        ];

        $lowerExercises = [
            [53, 6,  2, 210], // Squat
            [13, 8,  2, 180], // RDL
            [59, 12, 2, 90],  // Leg extension
            [60, 12, 2, 90],  // Leg curl
            [71, 20, 1, 60],  // Calf raise
        ];

        for ($week = 1; $week <= 6; $week++) {
            $sets = $week >= 5 ? 3 : 4;
            $this->buildSession($template->id, $week, 1, "Upper A — Sett. {$week}", $upperExercises, $sets);
            $this->buildSession($template->id, $week, 2, "Lower A — Sett. {$week}", $lowerExercises, $sets);
            $this->buildSession($template->id, $week, 3, "Upper B — Sett. {$week}", $upperExercises, $sets);
            $this->buildSession($template->id, $week, 4, "Lower B — Sett. {$week}", $lowerExercises, $sets);
        }

        $this->command->info("Template '{$name}' creato.");
    }

    /** @param array<int, array{0:int,1:int,2:int,3:int}> $exercises */
    private function buildSession(int $templateId, int $weekNumber, int $orderInWeek, string $name, array $exercises, int $sets): void
    {
        $session = TemplateSession::create([
            'template_id' => $templateId,
            'week_number' => $weekNumber,
            'name' => $name,
            'order_in_week' => $orderInWeek,
        ]);

        foreach ($exercises as $i => [$exerciseId, $reps, $rir, $rest]) {
            TemplateSessionExercise::create([
                'template_session_id' => $session->id,
                'exercise_id' => $exerciseId,
                'order_in_session' => $i + 1,
                'technique_type' => 'straight',
                'planned_sets_count' => $sets,
                'planned_reps' => $reps,
                'planned_rir' => $rir,
                'planned_rest_sec' => $rest,
                'note' => null,
                'group_key' => null,
                'group_type' => null,
                'tempo' => null,
            ]);
        }
    }
}
