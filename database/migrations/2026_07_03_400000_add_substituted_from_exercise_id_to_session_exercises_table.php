<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_exercises', function (Blueprint $table) {
            $table->unsignedInteger('substituted_from_exercise_id')
                ->nullable()
                ->after('exercise_id');

            $table->foreign('substituted_from_exercise_id')
                ->references('id')
                ->on('exercises')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('session_exercises', function (Blueprint $table) {
            $table->dropForeign(['substituted_from_exercise_id']);
            $table->dropColumn('substituted_from_exercise_id');
        });
    }
};
