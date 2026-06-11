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
        Schema::create('session_exercise_groups', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('session_id');
            $table->enum('group_type', ['superset', 'giant_set', 'circuit']);
            $table->unsignedSmallInteger('order_in_session');
            $table->unsignedTinyInteger('rounds')->default(3);
            $table->unsignedSmallInteger('rest_between_rounds_sec')->nullable();
            $table->foreign('session_id')->references('id')->on('training_sessions')->onDelete('cascade');
            // Nessun timestamp
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_exercise_groups');
    }
};
