<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_readiness_checks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('training_session_id')->unique();
            $table->foreign('training_session_id')
                ->references('id')
                ->on('training_sessions')
                ->cascadeOnDelete();

            // Scala 0-3: 0 = pessimo, 3 = ottimo (coerente con session_feedbacks)
            $table->tinyInteger('sleep_quality')->unsigned();
            $table->tinyInteger('stress_level')->unsigned();
            $table->tinyInteger('soreness_level')->unsigned();
            $table->tinyInteger('joint_status')->unsigned();

            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_readiness_checks');
    }
};
