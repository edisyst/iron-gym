<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            // chiave testuale come PK: nessun id surrogato, lookup diretto
            $table->string('key', 96)->primary();
            $table->json('value');
            $table->timestamps();
        });

        // I feature flag globali erano risolti per-utente da Pennant: le righe
        // memorizzate vincevano sul definer, rendendo inefficace il toggle da
        // backoffice. Si azzerano cosi' che il definer rilegga da settings.
        DB::table('features')->where('name', 'group_classes')->delete();
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');

        DB::table('features')->where('name', 'group_classes')->delete();
    }
};
