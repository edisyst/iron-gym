<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PilotSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPlans();
        $this->seedManagerAccount();
    }

    private function seedPlans(): void
    {
        foreach (config('pilot.plans', []) as $plan) {
            SubscriptionPlan::firstOrCreate(
                ['name' => $plan['name']],
                [
                    'price_cents' => $plan['price_cents'],
                    'duration_days' => $plan['duration_days'],
                    'max_accesses' => $plan['max_accesses'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Piani abbonamento creati/verificati.');
    }

    private function seedManagerAccount(): void
    {
        $email = config('pilot.manager_email');
        $password = config('pilot.manager_password');

        $manager = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Gestore',
                'password' => Hash::make($password),
            ]
        );

        $manager->assignRole('gestore');

        $this->command->info("Account gestore pronto: {$email}");
    }
}
