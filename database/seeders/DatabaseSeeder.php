<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Esegue tutti i seeder nell'ordine corretto.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ExerciseSeeder::class,
            ExerciseDescriptionSeeder::class,
            PlateInventorySeeder::class,
            DumbbellInventorySeeder::class,
            OpeningHoursSeeder::class,
            CommunicationTemplateSeeder::class,
        ]);

        // Seeder solo in ambiente locale
        if (app()->isLocal()) {
            $this->call([
                DemoSeeder::class,
                DemoTemplatesSeeder::class,
                TrainingHistorySeeder::class,
                ActiveMesocycleSeeder::class,
                ProgressDemoSeeder::class,
                // Prima di BookingDemoSeeder: crea le definizioni corso con il
                // palinsesto ricorrente, cosi' le occorrenze nascono da uno
                // ClassSchedule invece che sciolte.
                GroupClassSeeder::class,
                BookingDemoSeeder::class,
                R09R31DemoSeeder::class,
            ]);
        }
    }
}
