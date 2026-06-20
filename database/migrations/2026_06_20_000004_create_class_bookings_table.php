<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_bookings', function (Blueprint $table) {
            $table->id();
            // Corso prenotato
            $table->foreignId('class_id')->constrained('group_classes')->cascadeOnDelete();
            // Tesserato iscritto
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->enum('status', ['confirmed', 'waitlisted', 'cancelled'])->default('confirmed');
            // Posizione in waitlist (null se confirmed o cancelled)
            $table->tinyInteger('position')->unsigned()->nullable();
            // Nessun updated_at: una prenotazione corso non viene mai aggiornata parzialmente
            $table->timestamp('created_at')->nullable();

            // Un tesserato può avere al massimo una iscrizione attiva per corso
            $table->unique(['class_id', 'member_id'], 'uq_class_member');
            $table->index(['class_id', 'status'], 'idx_class_booking_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_bookings');
    }
};
