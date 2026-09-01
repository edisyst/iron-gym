<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Laravel\Pennant\Feature;

class SettingsFlagSeeder extends Seeder
{
    /** Valori pilota per ogni flag gestibile da UI. */
    private const PILOT_VALUES = [
        // Moduli
        'group_classes' => true,
        'messaging' => true,
        'pt_bookings' => true,
        'financial_reports' => true,
        'periodization_engine' => true,
        // Sessione atleta
        'readiness_check' => true,
        'exercise_substitution' => true,
        'session_recap' => true,
        'personal_records' => true,
        'weekly_volume' => true,
        // Sistema
        'push_notifications' => false,
        'outbound_notifications' => true,
        'in_app_feedback' => false,
        'public_api' => false,
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $flags = config('features.managed_flags', []);

        foreach ($flags as $flag => $meta) {
            $value = self::PILOT_VALUES[$flag] ?? $meta['default'];
            Setting::write($meta['settings_key'], $value);
        }

        Feature::purge(array_keys($flags));

        $this->command->info('Settings flag scritti: '.implode(', ', array_keys($flags)));
    }
}
