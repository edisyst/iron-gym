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
        Schema::create('workout_templates', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->enum('goal', ['hypertrophy', 'strength', 'cut', 'recomp', 'peaking', 'general'])->default('hypertrophy');
            $table->enum('periodization_model', ['linear', 'undulating_dup', 'block'])->default('linear');
            $table->unsignedTinyInteger('weeks_count')->default(5);
            $table->unsignedTinyInteger('days_per_week')->default(4);
            // created_by → users.id; FK esplicita aggiunta in futuro via ALTER
            $table->unsignedBigInteger('created_by');
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_templates');
    }
};
