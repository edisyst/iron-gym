<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pt_bookings', function (Blueprint $table) {
            $table->id();
            // Trainer che eroga la sessione
            $table->foreignId('trainer_id')->constrained('users')->restrictOnDelete();
            // Tesserato prenotante
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            // Sessione di allenamento collegata (opzionale, viene linkata dopo l'esecuzione)
            // unsignedInteger perché training_sessions.id è unsignedInteger (32-bit)
            $table->unsignedInteger('session_id')->nullable();
            $table->foreign('session_id')->references('id')->on('training_sessions')->nullOnDelete();
            $table->date('booked_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed', 'no_show'])
                ->default('pending');
            // User che ha effettuato la cancellazione (trainer, gestore o membro stesso)
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            // Scadenza entro cui la cancellazione è gratuita (default: 24h prima)
            $table->dateTime('cancellation_deadline')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['trainer_id', 'booked_date'], 'idx_pt_trainer_date');
            $table->index('member_id', 'idx_pt_member');
            $table->index('status', 'idx_pt_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pt_bookings');
    }
};
