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
        Schema::create('microcycle_weeks', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('mesocycle_id');
            $table->unsignedTinyInteger('week_number');
            $table->tinyInteger('is_deload')->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->unique(['mesocycle_id', 'week_number'], 'uq_meso_week');
            $table->foreign('mesocycle_id')->references('id')->on('mesocycles')->onDelete('cascade');
            // Nessun timestamp
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('microcycle_weeks');
    }
};
