<?php

namespace Database\Seeders;

use App\Models\ClassOccurrence;
use App\Models\Exercise;
use App\Models\ExerciseSet;
use App\Models\Member;
use App\Models\Message;
use App\Models\PersonalRecord;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Dati demo per le funzionalita introdotte da R09 a R31.
 * Dipende da DemoSeeder + TrainingHistorySeeder + BookingDemoSeeder.
 * Idempotente: controlla esistenza prima di inserire.
 */
class R09R31DemoSeeder extends Seeder
{
    public function run(): void
    {
        $athlete = User::where('email', 'atleta@atleta.atleta')->first();
        $trainer1 = User::where('email', 'trainer@trainer.trainer')->first();
        $trainer2 = User::where('email', 'trainer2@trainer.trainer')->first();

        if (! $athlete || ! $trainer1) {
            $this->command->warn('Utenti demo non trovati. Esegui prima DemoSeeder.');

            return;
        }

        $member = Member::where('user_id', $athlete->id)->first();

        $this->seedPersonalRecords($athlete);
        $this->seedMessages($athlete, $trainer1, $trainer2);
        $this->seedSuspendedSubscription();
        $this->seedMembersWithNotes();
        $this->seedNotifications($athlete, $member);

        $this->command->info('R09R31DemoSeeder: PR, messaggi, sospensione, note, notifiche creati.');
    }

    private function seedPersonalRecords(User $athlete): void
    {
        if (PersonalRecord::where('athlete_id', $athlete->id)->exists()) {
            return;
        }

        $exercises = [
            'barbell_bench_press' => ['label' => 'Panca piana', 'values' => [95.0, 100.0, 102.5, 105.0, 107.5]],
            'conventional_deadlift' => ['label' => 'Stacco da terra', 'values' => [150.0, 155.0, 160.0, 162.5, 165.0]],
            'back_squat_high_bar' => ['label' => 'Squat', 'values' => [120.0, 125.0, 127.5, 130.0, 132.5]],
            'overhead_press_standing' => ['label' => 'Lento avanti', 'values' => [60.0, 62.5, 65.0, 65.0, 67.5]],
            'barbell_curl' => ['label' => 'Curl bilanciere', 'values' => [40.0, 42.5, 42.5, 45.0, 47.5]],
        ];

        foreach ($exercises as $slug => $data) {
            $exercise = Exercise::where('slug', $slug)->first();

            if (! $exercise) {
                continue;
            }

            // Trova un ExerciseSet reale dell'atleta per questo esercizio (richiesto NOT NULL)
            $exerciseSet = ExerciseSet::whereHas('sessionExercise.session.week.mesocycle', function ($q) use ($athlete) {
                $q->where('athlete_id', $athlete->id);
            })->whereHas('sessionExercise', fn ($q) => $q->where('exercise_id', $exercise->id))
                ->where('is_warmup', 0)
                ->orderByDesc('id')
                ->first();

            if (! $exerciseSet) {
                continue;
            }

            foreach ($data['values'] as $i => $value) {
                $daysAgo = (count($data['values']) - $i - 1) * 14 + random_int(0, 5);
                PersonalRecord::create([
                    'athlete_id' => $athlete->id,
                    'exercise_id' => $exercise->id,
                    'exercise_set_id' => $exerciseSet->id,
                    'record_type' => 'e1rm',
                    'value' => $value,
                    'achieved_at' => now()->subDays($daysAgo),
                ]);
            }
        }
    }

    private function seedMessages(User $athlete, User $trainer1, ?User $trainer2): void
    {
        if (Message::where('sender_id', $trainer1->id)->where('receiver_id', $athlete->id)->exists()) {
            return;
        }

        $thread = [
            [$trainer1, $athlete, 'Ciao! Come sono andati gli allenamenti questa settimana?', now()->subDays(10)],
            [$athlete, $trainer1, 'Bene, la panca e andata molto bene — ho fatto 3x8 a 100kg.', now()->subDays(10)->addHours(2)],
            [$trainer1, $athlete, 'Ottimo! La settimana prossima proviamo ad aumentare a 102.5kg.', now()->subDays(10)->addHours(3)],
            [$athlete, $trainer1, 'Perfetto, ci vediamo giovedi allora.', now()->subDays(10)->addHours(4)],

            [$trainer1, $athlete, 'Ricordati di fare lo stretching post-sessione, soprattutto per il petto.', now()->subDays(7)],
            [$athlete, $trainer1, 'Lo faccio regolarmente, grazie per il reminder.', now()->subDays(7)->addHours(1)],

            [$trainer1, $athlete, 'Come ti senti oggi? Domani abbiamo sessione PT.', now()->subDays(2)],
            [$athlete, $trainer1, 'Benissimo, pronto per allenare duro!', now()->subDays(2)->addHours(3)],
            [$trainer1, $athlete, 'Perfetto. Ci vediamo alle 9:00. Porta scarpe da ginnastica.', now()->subDays(1)],
        ];

        foreach ($thread as [$sender, $receiver, $body, $date]) {
            Message::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'body' => $body,
                'read_at' => $date->lt(now()->subDays(3)) ? $date->addMinutes(10) : null,
                'created_at' => $date,
            ]);
        }

        if ($trainer2) {
            $thread2 = [
                [$trainer2, $athlete, 'Salve! Sono Elena, il tuo trainer per i corsi collettivi. Ti aspetto allo Spinning di domani!', now()->subDays(3)],
                [$athlete, $trainer2, 'Grazie! Non vedo l\'ora.', now()->subDays(3)->addHours(1)],
                [$trainer2, $athlete, 'Porta una borraccia d\'acqua — il corso e intenso!', now()->subDays(1)->addHours(2)],
            ];

            foreach ($thread2 as [$sender, $receiver, $body, $date]) {
                Message::create([
                    'sender_id' => $sender->id,
                    'receiver_id' => $receiver->id,
                    'body' => $body,
                    'read_at' => null,
                    'created_at' => $date,
                ]);
            }
        }
    }

    private function seedSuspendedSubscription(): void
    {
        if (Subscription::where('status', 'suspended')->exists()) {
            return;
        }

        $member = Member::whereHas('subscriptions', fn ($q) => $q->where('status', 'active'))
            ->orderByDesc('id')
            ->skip(2)
            ->first();

        if (! $member) {
            return;
        }

        $sub = $member->subscriptions()->where('status', 'active')->first();

        if ($sub) {
            $sub->update(['status' => 'suspended']);
        }
    }

    private function seedMembersWithNotes(): void
    {
        $membersWithNotes = [
            ['email' => 'giovanni.ferrari@example.com', 'notes' => 'Problema alla spalla destra — evitare esercizi overhead. Medico sportivo dottor Bianchi.'],
            ['email' => 'marco.ricci@example.com', 'notes' => 'Preferisce orari mattutini. Ha chiesto di essere contattato per rinnovo abbonamento.'],
        ];

        foreach ($membersWithNotes as $data) {
            Member::where('email', $data['email'])
                ->whereNull('notes')
                ->update(['notes' => $data['notes']]);
        }
    }

    private function seedNotifications(User $athlete, ?Member $member): void
    {
        if (DB::table('notifications')->where('notifiable_id', $athlete->id)->exists()) {
            return;
        }

        $occurrence = ClassOccurrence::where('status', 'planned')
            ->whereDate('date', '>', today())
            ->first();

        $notifications = [];

        // Notifica promemoria corso collettivo (R11)
        if ($occurrence && $member) {
            $className = $occurrence->groupClass->name ?? 'Functional Training';
            $classTime = substr($occurrence->start_time, 0, 5);
            $notifications[] = [
                'id' => Str::uuid()->toString(),
                'type' => 'App\\Notifications\\ClassReminderNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $athlete->id,
                'data' => json_encode([
                    'type' => 'class_reminder',
                    'occurrence_id' => $occurrence->id,
                    'message' => "Domani hai {$className} alle {$classTime}.",
                ]),
                'read_at' => null,
                'created_at' => now()->subHours(14),
                'updated_at' => now()->subHours(14),
            ];
        }

        // Notifica scadenza abbonamento (R10 centro notifiche)
        $notifications[] = [
            'id' => Str::uuid()->toString(),
            'type' => 'App\\Notifications\\SubscriptionExpiryNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $athlete->id,
            'data' => json_encode([
                'type' => 'subscription_expiry',
                'message' => 'Il tuo abbonamento scade tra 20 giorni. Rinnova ora.',
            ]),
            'read_at' => now()->subHours(2),
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ];

        // Notifica nuovo messaggio — letta
        $notifications[] = [
            'id' => Str::uuid()->toString(),
            'type' => 'App\\Notifications\\NewMessageNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $athlete->id,
            'data' => json_encode([
                'type' => 'new_message',
                'message' => 'Hai un nuovo messaggio da Luca Bianchi.',
                'sender_name' => 'Luca Bianchi',
            ]),
            'read_at' => now()->subDays(5),
            'created_at' => now()->subDays(7),
            'updated_at' => now()->subDays(7),
        ];

        // Notifica cancellazione corso (R09 Step 4)
        if ($member) {
            $notifications[] = [
                'id' => Str::uuid()->toString(),
                'type' => 'App\\Notifications\\ClassOccurrenceCancelledNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $athlete->id,
                'data' => json_encode([
                    'type' => 'class_cancelled',
                    'message' => 'Il corso Yoga del 15/08 e stato cancellato.',
                ]),
                'read_at' => now()->subDays(3),
                'created_at' => now()->subDays(9),
                'updated_at' => now()->subDays(9),
            ];
        }

        DB::table('notifications')->insert($notifications);
    }
}
