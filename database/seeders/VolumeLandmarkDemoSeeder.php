<?php

namespace Database\Seeders;

use App\Models\AthleteVolumeLandmark;
use App\Models\Muscle;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Inserisce landmark di volume personalizzati per l'atleta con ID=5.
 * Valori intenzionalmente diversi dai default di config/volume_landmarks.php
 * per rendere visibile il pulsante "Ripristina default".
 * Idempotente: usa updateOrCreate.
 */
class VolumeLandmarkDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('VolumeLandmarkDemoSeeder: vietato in production.');

            return;
        }

        $athlete = User::find(5);

        if (! $athlete) {
            $this->command->error('Atleta con ID=5 non trovato.');

            return;
        }

        if (! $athlete->hasRole('atleta')) {
            $this->command->warn("User ID=5 ({$athlete->email}) non ha ruolo atleta.");
        }

        // Valori personalizzati per tutti i 22 muscoli del config — superiori ai default
        // per simulare un atleta avanzato che ha alzato i propri landmark nel tempo.
        // Copertura totale: rende visibile "Ripristina default" su ogni riga.
        $custom = [
            'pectoralis_major_sternal' => ['mev' => 10, 'mav_min' => 14, 'mav_max' => 22, 'mrv' => 24],
            'pectoralis_major_clavicular' => ['mev' => 8,  'mav_min' => 12, 'mav_max' => 20, 'mrv' => 22],
            'deltoid_anterior' => ['mev' => 8,  'mav_min' => 12, 'mav_max' => 18, 'mrv' => 22],
            'deltoid_lateral' => ['mev' => 10, 'mav_min' => 14, 'mav_max' => 22, 'mrv' => 26],
            'deltoid_posterior' => ['mev' => 8,  'mav_min' => 12, 'mav_max' => 18, 'mrv' => 22],
            'triceps_brachii' => ['mev' => 8,  'mav_min' => 12, 'mav_max' => 16, 'mrv' => 20],
            'biceps_brachii' => ['mev' => 8,  'mav_min' => 12, 'mav_max' => 18, 'mrv' => 22],
            'brachialis' => ['mev' => 6,  'mav_min' => 10, 'mav_max' => 14, 'mrv' => 18],
            'latissimus_dorsi' => ['mev' => 10, 'mav_min' => 14, 'mav_max' => 22, 'mrv' => 26],
            'trapezius_upper' => ['mev' => 6,  'mav_min' => 10, 'mav_max' => 14, 'mrv' => 18],
            'trapezius_middle' => ['mev' => 8,  'mav_min' => 12, 'mav_max' => 18, 'mrv' => 22],
            'rhomboids' => ['mev' => 8,  'mav_min' => 12, 'mav_max' => 16, 'mrv' => 20],
            'erector_spinae' => ['mev' => 8,  'mav_min' => 12, 'mav_max' => 16, 'mrv' => 18],
            'quadriceps' => ['mev' => 10, 'mav_min' => 14, 'mav_max' => 22, 'mrv' => 26],
            'hamstrings' => ['mev' => 8,  'mav_min' => 12, 'mav_max' => 18, 'mrv' => 22],
            'gluteus_maximus' => ['mev' => 8,  'mav_min' => 12, 'mav_max' => 20, 'mrv' => 24],
            'gluteus_medius' => ['mev' => 6,  'mav_min' => 10, 'mav_max' => 14, 'mrv' => 18],
            'adductors' => ['mev' => 6,  'mav_min' => 10, 'mav_max' => 14, 'mrv' => 18],
            'gastrocnemius' => ['mev' => 10, 'mav_min' => 14, 'mav_max' => 20, 'mrv' => 24],
            'soleus' => ['mev' => 10, 'mav_min' => 14, 'mav_max' => 20, 'mrv' => 24],
            'rectus_abdominis' => ['mev' => 6,  'mav_min' => 10, 'mav_max' => 16, 'mrv' => 20],
            'obliques' => ['mev' => 6,  'mav_min' => 10, 'mav_max' => 14, 'mrv' => 18],
        ];

        $muscles = Muscle::whereIn('slug', array_keys($custom))->get()->keyBy('slug');

        $inserted = 0;
        $updated = 0;

        foreach ($custom as $slug => $values) {
            $muscle = $muscles[$slug] ?? null;

            if (! $muscle) {
                $this->command->warn("  Muscolo non trovato: {$slug}");

                continue;
            }

            $existing = AthleteVolumeLandmark::where('athlete_id', 5)
                ->where('muscle_id', $muscle->id)
                ->first();

            AthleteVolumeLandmark::updateOrCreate(
                ['athlete_id' => 5, 'muscle_id' => $muscle->id],
                [
                    'mev' => $values['mev'],
                    'mav_min' => $values['mav_min'],
                    'mav_max' => $values['mav_max'],
                    'mrv' => $values['mrv'],
                    'updated_by' => null,
                ]
            );

            $existing ? $updated++ : $inserted++;
        }

        $this->command->info("VolumeLandmarkDemoSeeder: {$inserted} inseriti, {$updated} aggiornati per atleta ID=5 ({$athlete->email}).");
    }
}
