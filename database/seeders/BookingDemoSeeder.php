<?php

namespace Database\Seeders;

use App\Models\ClassBooking;
use App\Models\ClassOccurrence;
use App\Models\GroupClass;
use App\Models\Member;
use App\Models\PtBooking;
use App\Models\TrainerAvailability;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BookingDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Recupera trainer esistenti (creati da DemoSeeder)
        $trainer1 = User::where('email', 'trainer@trainer.trainer')->first();
        $trainer2 = User::where('email', 'trainer2@trainer.trainer')->first();

        if (! $trainer1 || ! $trainer2) {
            $this->command->warn('Trainer non trovati. Esegui prima DemoSeeder.');

            return;
        }

        $members = Member::orderBy('id')->take(6)->get();

        if ($members->count() < 3) {
            $this->command->warn('Membri insufficienti. Esegui prima DemoSeeder.');

            return;
        }

        $this->seedTrainerAvailability($trainer1, $trainer2);
        $this->seedPtBookings($trainer1, $trainer2, $members);
        $this->seedGroupClasses($trainer1, $trainer2, $members);
    }

    private function seedTrainerAvailability(User $trainer1, User $trainer2): void
    {
        // Evita duplicati
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

    private function seedPtBookings(User $trainer1, User $trainer2, $members): void
    {
        if (PtBooking::exists()) {
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
            PtBooking::create([
                'trainer_id' => $trainer->id,
                'member_id' => $member->id,
                'session_id' => null,
                'booked_date' => $date->toDateString(),
                'start_time' => $start,
                'end_time' => $end,
                'status' => $status,
                'cancelled_by' => null,
                'cancellation_reason' => $status === 'cancelled' ? 'Impegno personale' : null,
                'cancellation_deadline' => $date->copy()->subDay()->setTime(20, 0),
                'notes' => null,
            ]);
        }
    }

    private function seedGroupClasses(User $trainer1, User $trainer2, $members): void
    {
        if (ClassOccurrence::exists()) {
            return;
        }

        $today = Carbon::today();

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

        $definitionCache = [];

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

            $occurrence = ClassOccurrence::create([
                'group_class_id' => $groupClass->id,
                'class_schedule_id' => null,
                'date' => $scheduledAt->toDateString(),
                'start_time' => $scheduledAt->format('H:i:s'),
                'end_time' => $endTime,
                'trainer_id' => $data['trainer']->id,
                'capacity' => $data['max_participants'],
                'status' => $data['status'],
            ]);

            foreach ($data['participants'] as $i => $member) {
                $isWaitlist = $i >= $data['max_participants'];
                ClassBooking::create([
                    'class_occurrence_id' => $occurrence->id,
                    'member_id' => $member->id,
                    'status' => $isWaitlist ? 'waitlisted' : 'confirmed',
                    'position' => $isWaitlist ? ($i - $data['max_participants'] + 1) : null,
                ]);
            }
        }
    }
}
