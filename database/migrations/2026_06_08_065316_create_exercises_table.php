<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->string('slug', 128)->unique();
            $table->string('name_it', 255);
            $table->text('description')->nullable();
            // FK verso movement_patterns: compound_pattern_id e joint_action_id sono mutuamente esclusive (CHECK XOR sotto)
            $table->unsignedInteger('compound_pattern_id')->nullable();
            $table->unsignedInteger('joint_action_id')->nullable();
            $table->enum('mechanic', ['compound', 'isolation']);
            $table->enum('plane', ['sagittal', 'frontal', 'transverse', 'multiplanar'])->default('sagittal');
            $table->enum('laterality', ['bilateral', 'unilateral_alternating', 'unilateral_isolated'])->default('bilateral');
            $table->enum('skill_level', ['beginner', 'intermediate', 'advanced'])->default('intermediate');
            $table->enum('measurement_type', ['reps_weight', 'reps_only', 'time', 'time_weight', 'distance', 'isometric_hold'])->default('reps_weight');
            $table->string('video_url', 512)->nullable();
            $table->string('thumbnail_url', 512)->nullable();
            // created_by riferisce users.id (BIGINT UNSIGNED); FK esplicita aggiunta in futuro via ALTER
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('compound_pattern_id', 'idx_exercises_compound_pattern');
            $table->index('joint_action_id', 'idx_exercises_joint_action');
            $table->index('mechanic', 'idx_exercises_mechanic');
            $table->foreign('compound_pattern_id')->references('id')->on('movement_patterns')->onDelete('restrict');
            $table->foreign('joint_action_id')->references('id')->on('movement_patterns')->onDelete('restrict');
        });

        // Vincolo XOR solo su MySQL 8.0.16+: SQLite non supporta ALTER TABLE ... ADD CONSTRAINT CHECK
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE exercises ADD CONSTRAINT chk_pattern_xor CHECK (
                (compound_pattern_id IS NOT NULL AND joint_action_id IS NULL)
                OR  (compound_pattern_id IS NULL  AND joint_action_id IS NOT NULL)
            )");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
