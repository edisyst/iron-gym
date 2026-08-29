<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Laravel\Pennant\Feature;

class SettingsFlagSeeder extends Seeder
{
    /** Valori pilota per ogni flag gestibile da UI. */
    private const PILOT_VALUES = [
        'group_classes' => true,
        'periodization_engine' => true,
        'financial_reports' => true,
        'push_notifications' => false,
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
