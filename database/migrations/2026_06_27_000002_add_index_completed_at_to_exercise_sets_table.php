<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercise_sets', function (Blueprint $table) {
            $table->index('completed_at', 'idx_exercise_sets_completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('exercise_sets', function (Blueprint $table) {
            $table->dropIndex('idx_exercise_sets_completed_at');
        });
    }
};
