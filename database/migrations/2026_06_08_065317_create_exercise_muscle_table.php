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
        Schema::create('exercise_muscle', function (Blueprint $table) {
            // PK composita, nessun id autoincrement
            $table->unsignedInteger('exercise_id');
            $table->unsignedInteger('muscle_id');
            $table->enum('role', ['primary', 'secondary', 'stabilizer']);
            $table->unsignedTinyInteger('contribution_pct')->default(100);
            $table->primary(['exercise_id', 'muscle_id']);
            $table->foreign('exercise_id')->references('id')->on('exercises')->onDelete('cascade');
            $table->foreign('muscle_id')->references('id')->on('muscles')->onDelete('restrict');
            // Nessun timestamp: pivot puro
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_muscle');
    }
};
