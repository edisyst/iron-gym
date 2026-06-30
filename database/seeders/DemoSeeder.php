<?php

namespace Database\Seeders;

use App\Models\AccessLog;
use App\Models\Member;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Account staff per ogni ruolo
        $staff = [
            ['email' => 'admin@admin.admin',                        'name' => 'Mario Rossi',  'role' => 'gestore',       'password' => 'admin'],
            ['email' => 'trainer@trainer.trainer',                  'name' => 'Luca Bianchi', 'role' => 'trainer',       'password' => 'trainer'],
            ['email' => 'trainer2@trainer.trainer',                 'name' => 'Elena Russo',  'role' => 'trainer',       'password' => 'trainer'],
            ['email' => 'receptionist@receptionist.receptionist',   'name' => 'Sara Verdi',   'role' => 'receptionist',  'password' => 'receptionist'],
        ];

        $receptionist = null;
        foreach ($staff as $s) {
            $user = User::firstOrCreate(
                ['email' => $s['email']],
                [
                    'name' => $s['name'],
                    'password' => Hash::make($s['password']),
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles([$s['role']]);
            if ($s['role'] === 'receptionist') {
                $receptionist = $user;
            }
        }

        // Piani abbonamento
        $mensile = SubscriptionPlan::firstOrCreate(
            ['name' => 'Mensile'],
            ['price_cents' => 5000, 'duration_days' => 30, 'max_accesses' => null, 'is_active' => true]
        );
        $trimestrale = SubscriptionPlan::firstOrCreate(
            ['name' => 'Trimestrale'],
            ['price_cents' => 12000, 'duration_days' => 90, 'max_accesses' => null, 'is_active' => true]
        );

        // Tesserati con scadenze certificate miste
        $membersData = [
            [
                'first_name' => 'Atleta',
                'last_name' => 'Test',
                'email' => 'atleta@atleta.atleta',
                'date_of_birth' => '1985-03-12',
                'medical_cert_expiry' => now()->addMonths(6)->toDateString(),
            ],
            [
                'first_name' => 'Giovanni',
                'last_name' => 'Ferrari',
                'email' => 'giovanni.ferrari@example.com',
                'date_of_birth' => '1985-03-12',
                'medical_cert_expiry' => now()->addMonths(6)->toDateString(),
            ],
            [
                'first_name' => 'Alessia',
                'last_name' => 'Colombo',
                'email' => 'alessia.colombo@example.com',
                'date_of_birth' => '1992-07-24',
                'medical_cert_expiry' => now()->subDays(5)->toDateString(), // scaduto
            ],
            [
                'first_name' => 'Marco',
                'last_name' => 'Ricci',
                'email' => 'marco.ricci@example.com',
                'date_of_birth' => '1990-11-08',
                'medical_cert_expiry' => now()->addDays(20)->toDateString(), // in scadenza
            ],
            [
                'first_name' => 'Federica',
                'last_name' => 'Esposito',
                'email' => 'federica.esposito@example.com',
                'date_of_birth' => '1988-05-30',
                'medical_cert_expiry' => now()->addMonths(11)->toDateString(),
            ],
            [
                'first_name' => 'Davide',
                'last_name' => 'Martini',
                'email' => 'davide.martini@example.com',
                'date_of_birth' => '1995-02-14',
                'medical_cert_expiry' => null, // mancante
            ],
        ];

        $members = [];
        foreach ($membersData as $data) {
            $member = Member::firstOrCreate(
                ['email' => $data['email']],
                array_merge($data, ['is_active' => true])
            );

            // Crea User collegato per accesso app atleta
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['first_name'].' '.$data['last_name'],
                    'password' => Hash::make('atleta'),
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles(['atleta']);

            if ($member->user_id === null) {
                $member->update(['user_id' => $user->id]);
            }

            $members[] = $member;
        }

        // 2 abbonamenti attivi
        $sub1 = Subscription::firstOrCreate(
            ['member_id' => $members[0]->id, 'plan_id' => $mensile->id],
            [
                'started_at' => now()->subDays(10)->toDateString(),
                'expires_at' => now()->addDays(20)->toDateString(),
                'accesses_remaining' => null,
                'status' => 'active',
                'created_by' => $receptionist?->id,
            ]
        );
        $sub2 = Subscription::firstOrCreate(
            ['member_id' => $members[1]->id, 'plan_id' => $trimestrale->id],
            [
                'started_at' => now()->subDays(30)->toDateString(),
                'expires_at' => now()->addDays(60)->toDateString(),
                'accesses_remaining' => null,
                'status' => 'active',
                'created_by' => $receptionist?->id,
            ]
        );

        // 1 abbonamento scaduto
        Subscription::firstOrCreate(
            ['member_id' => $members[2]->id, 'plan_id' => $mensile->id],
            [
                'started_at' => now()->subDays(40)->toDateString(),
                'expires_at' => now()->subDays(10)->toDateString(),
                'status' => 'expired',
                'created_by' => $receptionist?->id,
            ]
        );

        // Abbonamenti per i tesserati rimanenti
        Subscription::firstOrCreate(
            ['member_id' => $members[3]->id, 'plan_id' => $trimestrale->id],
            [
                'started_at' => now()->subDays(5)->toDateString(),
                'expires_at' => now()->addDays(85)->toDateString(),
                'accesses_remaining' => null,
                'status' => 'active',
                'created_by' => $receptionist?->id,
            ]
        );
        Subscription::firstOrCreate(
            ['member_id' => $members[4]->id, 'plan_id' => $mensile->id],
            [
                'started_at' => now()->subDays(15)->toDateString(),
                'expires_at' => now()->addDays(15)->toDateString(),
                'accesses_remaining' => null,
                'status' => 'active',
                'created_by' => $receptionist?->id,
            ]
        );
        Subscription::firstOrCreate(
            ['member_id' => $members[5]->id, 'plan_id' => $mensile->id],
            [
                'started_at' => now()->subDays(3)->toDateString(),
                'expires_at' => now()->addDays(27)->toDateString(),
                'accesses_remaining' => null,
                'status' => 'active',
                'created_by' => $receptionist?->id,
            ]
        );

        // 10 access log nell'ultima settimana
        $activeSubs = [$sub1, $sub2];
        for ($i = 0; $i < 10; $i++) {
            $sub = $activeSubs[$i % 2];
            AccessLog::create([
                'member_id' => $sub->member_id,
                'subscription_id' => $sub->id,
                'checked_in_at' => now()->subDays(random_int(0, 6))->setHour(random_int(7, 21)),
                'checked_in_by' => $receptionist?->id,
            ]);
            $sub->increment('accesses_used');
        }
    }
}
