<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_class_id')->constrained('group_classes')->cascadeOnDelete();
            // Giorno della settimana: 0=lunedì..6=domenica — stessa convenzione di trainer_availability.day_of_week
            $table->tinyInteger('weekday')->unsigned();
            $table->time('start_time');
            // Trainer di default per questa fascia; può essere sovrascritto sulla singola occorrenza
            $table->foreignId('trainer_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['group_class_id', 'weekday'], 'idx_schedule_class_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};
