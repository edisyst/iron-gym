<?php

namespace Database\Seeders;

use App\Models\GroupClass;
use App\Models\ClassSchedule;
use App\Models\ClassOccurrence;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GroupClassSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $trainer = User::role('trainer')->first() ?? User::role('gestore')->firstOrFail();

        $definitions = [
            [
                'name'             => 'Yoga Flow',
                'description'      => 'Sessione di yoga per tutti i livelli. Migliora flessibilità e concentrazione.',
                'duration_minutes' => 60,
                'default_capacity' => 12,
                'weekday'          => 1, // martedì
                'start_time'       => '09:00:00',
            ],
            [
                'name'             => 'Functional Training',
                'description'      => 'Allenamento funzionale ad alta intensità con corpo libero e kettlebell.',
                'duration_minutes' => 45,
                'default_capacity' => 8,
                'weekday'          => 3, // giovedì
                'start_time'       => '18:30:00',
            ],
            [
                'name'             => 'Calisthenics',
                'description'      => 'Forza e controllo del corpo con esercizi a corpo libero progressivi.',
                'duration_minutes' => 75,
                'default_capacity' => 10,
                'weekday'          => 5, // sabato
                'start_time'       => '10:00:00',
            ],
            [
                'name'             => 'Pilates',
                'description'      => 'Rinforzo del core e postura. Adatto a ogni livello di fitness.',
                'duration_minutes' => 60,
                'default_capacity' => 15,
                'weekday'          => 2, // mercoledì
                'start_time'       => '07:00:00',
            ],
        ];

        foreach ($definitions as $def) {
            $slug = Str::slug($def['name']);

            $groupClass = GroupClass::firstOrCreate(
                ['slug' => $slug],
                [
                    'name'             => $def['name'],
                    'description'      => $def['description'],
                    'duration_minutes' => $def['duration_minutes'],
                    'default_capacity' => $def['default_capacity'],
                    'is_active'        => true,
                ]
            );

            // Abilita trainer
            $groupClass->trainers()->syncWithoutDetaching([$trainer->id]);

            // Palinsesto settimanale
            $schedule = ClassSchedule::firstOrCreate(
                [
                    'group_class_id' => $groupClass->id,
                    'weekday'        => $def['weekday'],
                    'start_time'     => $def['start_time'],
                ],
                [
                    'trainer_id' => $trainer->id,
                    'valid_from' => now()->toDateString(),
                    'valid_until' => null,
                    'is_active'  => true,
                ]
            );

            // Materializza occorrenze per le prossime 2 settimane
            $endTime = \Carbon\Carbon::createFromTimeString($def['start_time'])
                ->addMinutes($def['duration_minutes'])
                ->format('H:i:s');

            for ($week = 0; $week < 2; $week++) {
                // Trova la data del weekday per la settimana corrente/prossima
                // Convenzione: 0=lunedì (Carbon::MONDAY=1 → offset = weekday)
                $monday = Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeeks($week);
                $date = $monday->copy()->addDays($def['weekday']);

                if ($date->isPast()) {
                    continue;
                }

                ClassOccurrence::firstOrCreate(
                    [
                        'class_schedule_id' => $schedule->id,
                        'date'              => $date->toDateString(),
                    ],
                    [
                        'group_class_id'    => $groupClass->id,
                        'start_time'        => $def['start_time'],
                        'end_time'          => $endTime,
                        'trainer_id'        => $trainer->id,
                        'capacity'          => $def['default_capacity'],
                        'status'            => 'planned',
                    ]
                );
            }
        }
    }
}
