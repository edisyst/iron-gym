<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_classes', function (Blueprint $table) {
            $table->id();
            // Trainer responsabile del corso
            $table->foreignId('trainer_id')->constrained('users')->restrictOnDelete();
            $table->string('name', 128);
            $table->text('description')->nullable();
            // Data e ora di inizio del corso
            $table->dateTime('scheduled_at');
            // Durata in minuti
            $table->smallInteger('duration_minutes')->unsigned()->default(60);
            // Numero massimo di partecipanti confermati
            $table->tinyInteger('max_participants')->unsigned()->default(10);
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index('scheduled_at', 'idx_class_scheduled');
            $table->index('status', 'idx_class_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_classes');
    }
};
