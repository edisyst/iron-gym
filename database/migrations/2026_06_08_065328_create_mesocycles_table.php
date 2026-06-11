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
        Schema::create('mesocycles', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            // athlete_id e trainer_id → users.id; FK esplicite aggiunte in futuro via ALTER
            $table->unsignedBigInteger('athlete_id');
            $table->unsignedBigInteger('trainer_id');
            $table->unsignedInteger('template_id')->nullable();
            $table->string('name', 255);
            $table->enum('goal', ['hypertrophy', 'strength', 'cut', 'recomp', 'peaking', 'general'])->default('hypertrophy');
            $table->enum('periodization_model', ['linear', 'undulating_dup', 'block'])->default('linear');
            $table->date('start_date');
            $table->unsignedTinyInteger('weeks_count')->default(5);
            $table->enum('status', ['draft', 'active', 'completed', 'aborted'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('athlete_id', 'idx_mesocycles_athlete');
            $table->index('status', 'idx_mesocycles_status');
            $table->foreign('template_id')->references('id')->on('workout_templates')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesocycles');
    }
};
