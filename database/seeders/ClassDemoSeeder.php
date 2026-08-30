<?php

namespace Database\Seeders;

use App\Models\ClassBooking;
use App\Models\ClassOccurrence;
use App\Models\ClassSchedule;
use App\Models\GroupClass;
use App\Models\Member;
use App\Models\PtBooking;
use App\Models\TrainerAvailability;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Corsi collettivi, disponibilita' trainer, prenotazioni PT e booking demo.
 * Fonde: GroupClassSeeder + BookingDemoSeeder.
 * Idempotente: usa firstOrCreate per definizioni e occorrenze.
 */
class ClassDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $trainer1 = User::where('email', 'trainer@trainer.trainer')->first();
        $trainer2 = User::where('email', 'trainer2@trainer.trainer')->first();

        if (! $trainer1) {
            $this->command->warn('Trainer non trovati. Esegui prima CoreDemoSeeder.');

            return;
        }

        $members = Member::orderBy('id')->take(6)->get();

        if ($members->count() < 3) {
            $this->command->warn('Membri insufficienti. Esegui prima CoreDemoSeeder.');

            return;
        }

        $this->seedDefinitions($trainer1);
        $this->seedTrainerAvailability($trainer1, $trainer2);
        $this->seedPtBookings($trainer1, $trainer2, $members);
        $this->seedHistoricalOccurrences($trainer1, $trainer2, $members);
    }

    // -------------------------------------------------------------------------
    // Definizioni corsi, palinsesto ricorrente e occorrenze future
    // (ex GroupClassSeeder)
    // -------------------------------------------------------------------------

    private function seedDefinitions(User $trainer): void
    {
        $definitions = [
            [
                'name' => 'Yoga Flow',
                'description' => 'Sessione di yoga per tutti i livelli. Migliora flessibilità e concentrazione.',
                'duration_minutes' => 60,
                'default_capacity' => 12,
                'weekday' => 1, // martedì
                'start_time' => '09:00:00',
            ],
            [
                'name' => 'Functional Training',
                'description' => 'Allenamento funzionale ad alta intensità con corpo libero e kettlebell.',
                'duration_minutes' => 45,
                'default_capacity' => 8,
                'weekday' => 3, // giovedì
                'start_time' => '18:30:00',
            ],
            [
                'name' => 'Calisthenics',
                'description' => 'Forza e controllo del corpo con esercizi a corpo libero progressivi.',
                'duration_minutes' => 75,
                'default_capacity' => 10,
                'weekday' => 5, // sabato
                'start_time' => '10:00:00',
            ],
            [
                'name' => 'Pilates',
                'description' => 'Rinforzo del core e postura. Adatto a ogni livello di fitness.',
                'duration_minutes' => 60,
                'default_capacity' => 15,
                'weekday' => 2, // mercoledì
                'start_time' => '07:00:00',
            ],
        ];

        foreach ($definitions as $def) {
            $slug = Str::slug($def['name']);

            $groupClass = GroupClass::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $def['name'],
                    'description' => $def['description'],
                    'duration_minutes' => $def['duration_minutes'],
                    'default_capacity' => $def['default_capacity'],
                    'is_active' => true,
                ]
            );

            $groupClass->trainers()->syncWithoutDetaching([$trainer->id]);

            $schedule = ClassSchedule::firstOrCreate(
                [
                    'group_class_id' => $groupClass->id,
                    'weekday' => $def['weekday'],
                    'start_time' => $def['start_time'],
                ],
                [
                    'trainer_id' => $trainer->id,
                    'valid_from' => now()->toDateString(),
                    'valid_until' => null,
                    'is_active' => true,
                ]
            );

            // Materializza occorrenze per le prossime 2 settimane
            $endTime = Carbon::createFromTimeString($def['start_time'])
                ->addMinutes($def['duration_minutes'])
                ->format('H:i:s');

            for ($week = 0; $week < 2; $week++) {
                // Convenzione: 0=lunedì (Carbon::MONDAY=1 → offset = weekday)
                $monday = Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeeks($week);
                $date = $monday->copy()->addDays($def['weekday']);

                if ($date->isPast()) {
                    continue;
                }

                ClassOccurrence::firstOrCreate(
                    [
                        'class_schedule_id' => $schedule->id,
                        'date' => $date->toDateString(),
                    ],
                    [
                        'group_class_id' => $groupClass->id,
                        'start_time' => $def['start_time'],
                        'end_time' => $endTime,
                        'trainer_id' => $trainer->id,
                        'capacity' => $def['default_capacity'],
                        'status' => 'planned',
                    ]
                );
            }
        }
    }

    // -------------------------------------------------------------------------
    // Disponibilita' trainer
    // (ex BookingDemoSeeder::seedTrainerAvailability)
    // -------------------------------------------------------------------------

    private function seedTrainerAvailability(User $trainer1, ?User $trainer2): void
    {
        if (TrainerAvailability::where('trainer_id', $trainer1->id)->exists()) {
            return;
        }

        // Trainer1: Lun/Mer/Ven mattina + Lun/Mer/Gio pomeriggio
        $slots1 = [
            [1, '08:00', '12:00'],
            [3, '08:00', '12:00'],
            [5, '08:00', '12:00'],
            [1, '15:00', '19:00'],
            [3, '15:00', '19:00'],
            [4, '15:00', '19:00'],
        ];

        foreach ($slots1 as [$day, $start, $end]) {
            TrainerAvailability::create([
                'trainer_id' => $trainer1->id,
                'day_of_week' => $day,
                'specific_date' => null,
                'start_time' => $start,
                'end_time' => $end,
                'is_available' => true,
                'notes' => null,
            ]);
        }

        if (! $trainer2) {
            return;
        }

        // Trainer2: Mar/Gio/Sab
        $slots2 = [
            [2, '09:00', '13:00'],
            [4, '09:00', '13:00'],
            [6, '09:00', '13:00'],
            [2, '16:00', '20:00'],
            [4, '16:00', '20:00'],
        ];

        foreach ($slots2 as [$day, $start, $end]) {
            TrainerAvailability::create([
                'trainer_id' => $trainer2->id,
                'day_of_week' => $day,
                'specific_date' => null,
                'start_time' => $start,
                'end_time' => $end,
                'is_available' => true,
                'notes' => null,
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Prenotazioni PT (ex BookingDemoSeeder::seedPtBookings)
    // Guard rimosso: usa firstOrCreate per idempotenza granulare.
    // -------------------------------------------------------------------------

    private function seedPtBookings(User $trainer1, ?User $trainer2, $members): void
    {
        if (! $trainer2) {
            return;
        }

        $today = Carbon::today();

        $bookings = [
            // Settimana scorsa (storico)
            [$trainer1, $members[0], $today->copy()->subDays(7), '09:00', '10:00', 'completed'],
            [$trainer1, $members[1], $today->copy()->subDays(5), '10:00', '11:00', 'completed'],
            [$trainer2, $members[2], $today->copy()->subDays(6), '09:00', '10:00', 'completed'],
            [$trainer2, $members[3], $today->copy()->subDays(4), '16:00', '17:00', 'cancelled'],

            // Questa settimana
            [$trainer1, $members[0], $today->copy()->addDays(1), '09:00', '10:00', 'confirmed'],
            [$trainer1, $members[4], $today->copy()->addDays(1), '10:00', '11:00', 'confirmed'],
            [$trainer2, $members[1], $today->copy()->addDays(2), '09:00', '10:00', 'confirmed'],
            [$trainer2, $members[3], $today->copy()->addDays(2), '16:00', '17:00', 'pending'],
            [$trainer1, $members[2], $today->copy()->addDays(3), '15:00', '16:00', 'confirmed'],
            [$trainer2, $members[5], $today->copy()->addDays(4), '09:00', '10:00', 'confirmed'],

            // Prossima settimana
            [$trainer1, $members[0], $today->copy()->addDays(8), '09:00', '10:00', 'confirmed'],
            [$trainer1, $members[1], $today->copy()->addDays(8), '10:00', '11:00', 'confirmed'],
            [$trainer2, $members[2], $today->copy()->addDays(9), '09:00', '10:00', 'pending'],
            [$trainer1, $members[3], $today->copy()->addDays(10), '15:00', '16:00', 'confirmed'],
            [$trainer2, $members[4], $today->copy()->addDays(11), '16:00', '17:00', 'confirmed'],
        ];

        foreach ($bookings as [$trainer, $member, $date, $start, $end, $status]) {
            PtBooking::firstOrCreate(
                [
                    'trainer_id' => $trainer->id,
                    'member_id' => $member->id,
                    'booked_date' => $date->toDateString(),
                    'start_time' => $start,
                ],
                [
                    'end_time' => $end,
                    'status' => $status,
                    'session_id' => null,
                    'cancelled_by' => null,
                    'cancellation_reason' => $status === 'cancelled' ? 'Impegno personale' : null,
                    'cancellation_deadline' => $date->copy()->subDay()->setTime(20, 0),
                    'notes' => null,
                ]
            );
        }
    }

    // -------------------------------------------------------------------------
    // Occorrenze storiche con booking (ex BookingDemoSeeder::seedGroupClasses)
    // Guard globale rimosso: usa firstOrCreate per idempotenza granulare.
    // -------------------------------------------------------------------------

    private function seedHistoricalOccurrences(User $trainer1, ?User $trainer2, $members): void
    {
        if (! $trainer2) {
            return;
        }

        $today = Carbon::today();
        $definitionCache = [];

        $classes = [
            // Settimana scorsa
            [
                'trainer' => $trainer1,
                'name' => 'Functional Training',
                'description' => 'Allenamento funzionale a corpo libero e kettlebell.',
                'scheduled_at' => $today->copy()->subDays(6)->setTime(9, 0),
                'duration_minutes' => 60,
                'max_participants' => 10,
                'status' => 'completed',
                'participants' => [$members[0], $members[1], $members[2]],
            ],
            [
                'trainer' => $trainer2,
                'name' => 'Stretching & Mobility',
                'description' => 'Sessione di allungamento e mobilita articolare.',
                'scheduled_at' => $today->copy()->subDays(4)->setTime(18, 0),
                'duration_minutes' => 45,
                'max_participants' => 12,
                'status' => 'completed',
                'participants' => [$members[3], $members[4]],
            ],

            // Questa settimana
            [
                'trainer' => $trainer1,
                'name' => 'Circuit Training',
                'description' => 'Circuito ad alta intensita su 6 stazioni.',
                'scheduled_at' => $today->copy()->addDays(1)->setTime(10, 0),
                'duration_minutes' => 60,
                'max_participants' => 8,
                'status' => 'planned',
                'participants' => [$members[0], $members[1], $members[2], $members[3], $members[4], $members[5]],
            ],
            [
                'trainer' => $trainer2,
                'name' => 'Spinning',
                'description' => 'Ciclismo indoor con musica ad alto ritmo.',
                'scheduled_at' => $today->copy()->addDays(2)->setTime(18, 30),
                'duration_minutes' => 45,
                'max_participants' => 15,
                'status' => 'planned',
                'participants' => [$members[1], $members[3], $members[5]],
            ],
            [
                'trainer' => $trainer1,
                'name' => 'Functional Training',
                'description' => 'Allenamento funzionale a corpo libero e kettlebell.',
                'scheduled_at' => $today->copy()->addDays(3)->setTime(9, 0),
                'duration_minutes' => 60,
                'max_participants' => 10,
                'status' => 'planned',
                'participants' => [$members[0], $members[2], $members[4]],
            ],
            [
                'trainer' => $trainer2,
                'name' => 'Yoga',
                'description' => 'Yoga dinamico per atleti — respiro e forza.',
                'scheduled_at' => $today->copy()->addDays(4)->setTime(8, 0),
                'duration_minutes' => 75,
                'max_participants' => 12,
                'status' => 'planned',
                'participants' => [$members[1], $members[2], $members[3], $members[5]],
            ],

            // Prossima settimana
            [
                'trainer' => $trainer1,
                'name' => 'Circuit Training',
                'description' => 'Circuito ad alta intensita su 6 stazioni.',
                'scheduled_at' => $today->copy()->addDays(8)->setTime(10, 0),
                'duration_minutes' => 60,
                'max_participants' => 8,
                'status' => 'planned',
                'participants' => [$members[0], $members[2]],
            ],
            [
                'trainer' => $trainer2,
                'name' => 'Spinning',
                'description' => 'Ciclismo indoor con musica ad alto ritmo.',
                'scheduled_at' => $today->copy()->addDays(9)->setTime(18, 30),
                'duration_minutes' => 45,
                'max_participants' => 15,
                'status' => 'planned',
                'participants' => [$members[4], $members[5]],
            ],
            [
                'trainer' => $trainer1,
                'name' => 'Stretching & Mobility',
                'description' => 'Sessione di allungamento e mobilita articolare.',
                'scheduled_at' => $today->copy()->addDays(11)->setTime(8, 0),
                'duration_minutes' => 45,
                'max_participants' => 12,
                'status' => 'planned',
                'participants' => [$members[1], $members[3]],
            ],
        ];

        foreach ($classes as $data) {
            $name = $data['name'];
            $slug = Str::slug($name);

            if (! isset($definitionCache[$slug])) {
                $definitionCache[$slug] = GroupClass::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'description' => $data['description'],
                        'duration_minutes' => $data['duration_minutes'],
                        'default_capacity' => $data['max_participants'],
                        'is_active' => true,
                    ]
                );
            }

            $groupClass = $definitionCache[$slug];
            $groupClass->trainers()->syncWithoutDetaching([$data['trainer']->id]);

            $scheduledAt = $data['scheduled_at'];
            $endTime = $scheduledAt->copy()->addMinutes($data['duration_minutes'])->format('H:i:s');

            $occurrence = ClassOccurrence::firstOrCreate(
                [
                    'group_class_id' => $groupClass->id,
                    'date' => $scheduledAt->toDateString(),
                    'start_time' => $scheduledAt->format('H:i:s'),
                ],
                [
                    'class_schedule_id' => null,
                    'end_time' => $endTime,
                    'trainer_id' => $data['trainer']->id,
                    'capacity' => $data['max_participants'],
                    'status' => $data['status'],
                ]
            );

            foreach ($data['participants'] as $i => $member) {
                $isWaitlist = $i >= $data['max_participants'];
                ClassBooking::firstOrCreate(
                    ['class_occurrence_id' => $occurrence->id, 'member_id' => $member->id],
                    [
                        'status' => $isWaitlist ? 'waitlisted' : 'confirmed',
                        'position' => $isWaitlist ? ($i - $data['max_participants'] + 1) : null,
                    ]
                );
            }
        }
    }
}
