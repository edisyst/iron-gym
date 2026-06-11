<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('template_session_exercises', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('template_session_id');
            $table->unsignedInteger('exercise_id');
            $table->unsignedSmallInteger('order_in_session');
            $table->enum('technique_type', [
                'straight', 'drop_set', 'rest_pause', 'myo_reps', 'cluster',
                'twenty_ones', 'pre_exhaustion', 'emom', 'amrap',
            ])->default('straight');
            $table->string('tempo', 7)->nullable();
            $table->unsignedTinyInteger('planned_sets_count');
            $table->unsignedSmallInteger('planned_reps')->nullable();
            $table->unsignedTinyInteger('planned_rir')->nullable();
            $table->unsignedSmallInteger('planned_rest_sec')->nullable();
            $table->text('note')->nullable();
            $table->foreign('template_session_id')->references('id')->on('template_sessions')->onDelete('cascade');
            $table->foreign('exercise_id')->references('id')->on('exercises')->onDelete('restrict');
            // Nessun timestamp
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_session_exercises');
    }
};
