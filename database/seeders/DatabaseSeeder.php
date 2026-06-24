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
        ]);

        // Seeder solo in ambiente locale
        if (app()->isLocal()) {
            $this->call([
                DemoSeeder::class,
                TrainingHistorySeeder::class,
            ]);
        }
    }
}
