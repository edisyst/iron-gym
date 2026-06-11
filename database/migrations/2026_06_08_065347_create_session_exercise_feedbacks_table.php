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
        Schema::create('session_exercise_feedbacks', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('session_exercise_id')->unique();
            $table->unsignedTinyInteger('joint_pain')->nullable();  // 0..3 specifico per articolazione coinvolta
            $table->unsignedTinyInteger('pump')->nullable();
            $table->text('note')->nullable();
            $table->foreign('session_exercise_id')->references('id')->on('session_exercises')->onDelete('cascade');
            // Nessun timestamp
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_exercise_feedbacks');
    }
};
