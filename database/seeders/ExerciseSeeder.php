<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExerciseSeeder extends Seeder
{
    public function run(): void
    {
        // Carica il file SQL con INSERT...SELECT via slug (nessun id hardcodato)
        $sql = file_get_contents(database_path('seeders/sql/exercises_seed.sql'));
        DB::unprepared($sql);
    }
}
