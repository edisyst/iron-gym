<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('body_measurements', function (Blueprint $table) {
            $table->id();
            // FK verso users (atleta)
            $table->foreignId('athlete_id')->constrained('users')->restrictOnDelete();
            $table->date('measured_at');
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('body_fat_pct', 4, 1)->nullable();
            $table->decimal('chest_cm', 5, 1)->nullable();
            $table->decimal('waist_cm', 5, 1)->nullable();
            $table->decimal('hips_cm', 5, 1)->nullable();
            $table->decimal('left_arm_cm', 5, 1)->nullable();
            $table->decimal('right_arm_cm', 5, 1)->nullable();
            $table->decimal('left_thigh_cm', 5, 1)->nullable();
            $table->decimal('right_thigh_cm', 5, 1)->nullable();
            $table->decimal('left_calf_cm', 5, 1)->nullable();
            $table->decimal('right_calf_cm', 5, 1)->nullable();
            $table->text('notes')->nullable();
            // Trainer che ha registrato la misurazione (nullable: può essere l'atleta stesso)
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['athlete_id', 'measured_at'], 'idx_athlete_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('body_measurements');
    }
};
