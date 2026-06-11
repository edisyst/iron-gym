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
        Schema::create('movement_patterns', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->string('slug', 64)->unique();
            $table->string('name_it', 128);
            $table->enum('category', ['compound_pattern', 'joint_action']);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->index('category', 'idx_movement_patterns_category');
            // Nessun timestamp: tabella di lookup stabile
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movement_patterns');
    }
};
