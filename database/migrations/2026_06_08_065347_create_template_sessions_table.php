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
        Schema::create('template_sessions', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('template_id');
            $table->unsignedTinyInteger('week_number');
            $table->string('name', 128);
            $table->unsignedTinyInteger('order_in_week');
            $table->foreign('template_id')->references('id')->on('workout_templates')->onDelete('cascade');
            // Nessun timestamp
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_sessions');
    }
};
