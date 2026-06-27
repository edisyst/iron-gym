<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mesocycles', function (Blueprint $table) {
            $table->foreign('athlete_id')
                ->references('id')->on('users')
                ->onDelete('restrict');

            $table->foreign('trainer_id')
                ->references('id')->on('users')
                ->onDelete('restrict');

            // athlete_id ha già index dalla migration originale; aggiungiamo trainer_id
            $table->index('trainer_id', 'idx_mesocycles_trainer_id');
        });
    }

    public function down(): void
    {
        Schema::table('mesocycles', function (Blueprint $table) {
            $table->dropForeign(['athlete_id']);
            $table->dropForeign(['trainer_id']);
            $table->dropIndex('idx_mesocycles_trainer_id');
        });
    }
};
