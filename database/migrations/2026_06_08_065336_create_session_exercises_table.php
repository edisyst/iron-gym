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
        Schema::create('session_exercises', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('session_id');
            $table->unsignedInteger('group_id')->nullable();
            $table->unsignedInteger('exercise_id');
            $table->unsignedSmallInteger('order_in_session');
            $table->unsignedTinyInteger('order_in_group')->nullable();
            $table->enum('technique_type', [
                'straight', 'drop_set', 'rest_pause', 'myo_reps', 'cluster',
                'twenty_ones', 'pre_exhaustion', 'emom', 'amrap',
            ])->default('straight');
            $table->string('tempo', 7)->nullable();
            $table->unsignedTinyInteger('planned_sets_count');
            $table->unsignedSmallInteger('planned_rest_sec')->nullable();
            $table->unsignedTinyInteger('intra_cluster_rest_sec')->nullable();
            $table->text('trainer_note')->nullable();
            $table->index(['session_id', 'order_in_session'], 'idx_session_exercises_order');
            $table->foreign('session_id')->references('id')->on('training_sessions')->onDelete('cascade');
            $table->foreign('group_id')->references('id')->on('session_exercise_groups')->onDelete('set null');
            $table->foreign('exercise_id')->references('id')->on('exercises')->onDelete('restrict');
            // Nessun timestamp
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_exercises');
    }
};
