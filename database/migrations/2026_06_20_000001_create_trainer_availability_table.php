<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_availability', function (Blueprint $table) {
            $table->id();
            // FK verso lo user con ruolo trainer
            $table->foreignId('trainer_id')->constrained('users')->restrictOnDelete();
            // Giorno della settimana ricorrente: 0=lunedì..6=domenica (convenzione ISO - 1)
            $table->tinyInteger('day_of_week')->unsigned()->nullable();
            // Data puntuale per override/eccezioni
            $table->date('specific_date')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            // false = blocco (eccezione di non disponibilità per quella data/giorno)
            $table->boolean('is_available')->default(true);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['trainer_id', 'day_of_week'], 'idx_trainer_day');
            $table->index(['trainer_id', 'specific_date'], 'idx_trainer_date');
        });

        // Vincolo XOR: obbligatorio avere esattamente uno tra day_of_week e specific_date
        // Applicato solo su MySQL (SQLite non supporta ADD CONSTRAINT CHECK via ALTER TABLE)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE trainer_availability ADD CONSTRAINT chk_availability_xor CHECK (
                (day_of_week IS NOT NULL AND specific_date IS NULL)
                OR (day_of_week IS NULL AND specific_date IS NOT NULL)
            )');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_availability');
    }
};
