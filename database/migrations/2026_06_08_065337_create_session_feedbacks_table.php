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
        Schema::create('session_feedbacks', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('session_id')->unique();
            // Feedback post-sessione su scala 0-3
            $table->unsignedTinyInteger('pump')->nullable();
            $table->unsignedTinyInteger('soreness_prev')->nullable();       // soreness residua dalla sessione precedente
            $table->unsignedTinyInteger('perceived_effort')->nullable();
            $table->unsignedTinyInteger('joint_pain')->nullable();
            $table->unsignedTinyInteger('performance')->nullable();
            $table->decimal('sleep_hours', 3, 1)->nullable();
            $table->unsignedTinyInteger('stress_level')->nullable();        // 0..3
            $table->text('note')->nullable();
            // Solo created_at, nessun updated_at: il feedback è immutabile dopo la compilazione
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->foreign('session_id')->references('id')->on('training_sessions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_feedbacks');
    }
};
