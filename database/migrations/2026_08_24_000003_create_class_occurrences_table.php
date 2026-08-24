<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_class_id')->constrained('group_classes')->restrictOnDelete();
            // Null per occorrenze una tantum non legate a palinsesto
            $table->foreignId('class_schedule_id')->nullable()->constrained('class_schedules')->nullOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->foreignId('trainer_id')->constrained('users')->restrictOnDelete();
            $table->tinyInteger('capacity')->unsigned();
            $table->enum('status', ['planned', 'cancelled', 'completed'])->default('planned');
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            // Idempotenza per il command di generazione: una sola occorrenza per palinsesto+data.
            // Le occorrenze una tantum (class_schedule_id NULL) non sono coperte dal vincolo (NULL != NULL in MySQL).
            $table->unique(['class_schedule_id', 'date'], 'uq_schedule_date');

            $table->index(['date', 'status'], 'idx_occurrence_date_status');
            $table->index(['trainer_id', 'date'], 'idx_occurrence_trainer_date');

            // Colonna helper per la data migration: id del vecchio group_class da cui questa occorrenza è stata creata.
            // Rimossa dalla migration 000005_transform_class_bookings dopo l'uso.
            $table->unsignedBigInteger('old_class_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_occurrences');
    }
};
