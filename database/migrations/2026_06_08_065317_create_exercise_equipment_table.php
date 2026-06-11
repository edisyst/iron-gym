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
        Schema::create('exercise_equipment', function (Blueprint $table) {
            $table->unsignedInteger('exercise_id');
            $table->unsignedInteger('equipment_id');
            $table->primary(['exercise_id', 'equipment_id']);
            $table->foreign('exercise_id')->references('id')->on('exercises')->onDelete('cascade');
            $table->foreign('equipment_id')->references('id')->on('equipment')->onDelete('restrict');
            // Nessun timestamp: pivot puro
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_equipment');
    }
};
