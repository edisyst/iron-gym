<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progress_photos', function (Blueprint $table) {
            $table->id();
            // FK verso users (atleta)
            $table->foreignId('athlete_id')->constrained('users')->restrictOnDelete();
            $table->date('taken_at');
            $table->enum('pose', ['front', 'back', 'side_left', 'side_right']);
            $table->string('file_path', 512);
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['athlete_id', 'taken_at'], 'idx_pp_athlete_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_photos');
    }
};
