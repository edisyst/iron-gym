<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('endpoint');
            $table->text('public_key')->nullable();
            $table->text('auth_token')->nullable();
            $table->timestamps();
        });

        // Indice prefissato su colonna TEXT: sintassi diversa per MySQL vs SQLite
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE push_subscriptions ADD UNIQUE KEY uq_endpoint (endpoint(191))');
        } else {
            // SQLite: crea l'indice senza prefisso (usato nei test)
            DB::statement('CREATE UNIQUE INDEX uq_endpoint ON push_subscriptions (endpoint)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
