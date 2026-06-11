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
        Schema::create('exercise_sets', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('session_exercise_id');
            $table->unsignedTinyInteger('set_index');
            // set_sequence_id raggruppa sub-set di drop/rest-pause/myo/cluster/21s
            $table->unsignedInteger('set_sequence_id')->nullable();
            $table->unsignedTinyInteger('sequence_index')->nullable();
            // set_subtype: es. "bottom_half","top_half","full_rom","activation","cluster","drop_1"
            $table->string('set_subtype', 32)->nullable();
            $table->tinyInteger('is_warmup')->default(0);
            // Prescrizione (planned)
            $table->unsignedSmallInteger('planned_reps')->nullable();       // NULL per AMRAP
            $table->decimal('planned_weight_kg', 6, 2)->nullable();
            $table->unsignedTinyInteger('planned_rir')->nullable();         // 0..10
            $table->decimal('planned_rpe', 3, 1)->nullable();               // 1.0..10.0
            $table->unsignedSmallInteger('planned_duration_sec')->nullable(); // EMOM/AMRAP/isometric
            // Esecuzione (actual)
            $table->unsignedSmallInteger('actual_reps')->nullable();
            $table->decimal('actual_weight_kg', 6, 2)->nullable();
            $table->unsignedTinyInteger('actual_rir')->nullable();
            $table->decimal('actual_rpe', 3, 1)->nullable();
            $table->unsignedSmallInteger('actual_duration_sec')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('note')->nullable();
            $table->index('session_exercise_id', 'idx_exercise_sets_session_exercise');
            $table->index(['set_sequence_id', 'sequence_index'], 'idx_exercise_sets_sequence');
            $table->foreign('session_exercise_id')->references('id')->on('session_exercises')->onDelete('cascade');
            // Nessun created_at/updated_at: ha completed_at esplicita come marker temporale
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_sets');
    }
};
