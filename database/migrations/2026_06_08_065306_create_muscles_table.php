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
        Schema::create('muscles', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->string('slug', 64)->unique();
            $table->string('name_it', 128);
            $table->string('muscle_group', 64);
            $table->string('muscle_head', 64)->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            // Nessun timestamp: tabella di lookup stabile
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muscles');
    }
};
