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
        Schema::create('athlete_volume_landmarks', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            // athlete_id → users.id (BIGINT UNSIGNED); FK esplicita aggiunta in futuro via ALTER
            $table->unsignedBigInteger('athlete_id');
            $table->unsignedInteger('muscle_id');
            $table->unsignedTinyInteger('mev');
            $table->unsignedTinyInteger('mav_min');
            $table->unsignedTinyInteger('mav_max');
            $table->unsignedTinyInteger('mrv');
            $table->text('notes')->nullable();
            // updated_by → users.id; FK esplicita aggiunta in futuro via ALTER
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['athlete_id', 'muscle_id'], 'uq_athlete_muscle');
            $table->foreign('muscle_id')->references('id')->on('muscles')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('athlete_volume_landmarks');
    }
};
