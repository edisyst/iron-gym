<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('athlete_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('exercise_id');
            $table->foreign('exercise_id')->references('id')->on('exercises')->cascadeOnDelete();
            $table->unsignedInteger('exercise_set_id');
            $table->foreign('exercise_set_id')->references('id')->on('exercise_sets')->cascadeOnDelete();
            $table->enum('record_type', ['e1rm', 'max_weight', 'max_reps_at_weight']);
            $table->decimal('value', 7, 2);
            $table->timestamp('achieved_at');
            $table->timestamps();

            $table->index(['athlete_id', 'exercise_id', 'record_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_records');
    }
};
