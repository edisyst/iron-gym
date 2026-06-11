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
        // Nota: usiamo training_sessions per evitare collisione con la tabella sessions delle HTTP sessions di Laravel
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('microcycle_week_id');
            $table->string('name', 128);
            $table->unsignedTinyInteger('order_in_week');
            $table->date('scheduled_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['planned', 'in_progress', 'completed', 'skipped'])->default('planned');
            $table->text('athlete_notes')->nullable();
            $table->text('trainer_notes')->nullable();
            $table->timestamps();
            $table->index('status', 'idx_training_sessions_status');
            $table->index('scheduled_date', 'idx_training_sessions_scheduled');
            $table->foreign('microcycle_week_id')->references('id')->on('microcycle_weeks')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};
