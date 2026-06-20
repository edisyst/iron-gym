<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('communication_templates')->nullOnDelete();
            $table->enum('channel', ['email', 'sms', 'push', 'internal']);
            $table->string('subject', 255)->nullable();
            $table->text('body')->nullable();
            $table->enum('status', ['sent', 'failed', 'pending'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('member_id', 'idx_member');
            $table->index(['status', 'sent_at'], 'idx_status_sent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_logs');
    }
};
