<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('template_session_exercises', function (Blueprint $table) {
            // UUID condiviso tra esercizi dello stesso superset/giant_set/circuit
            $table->char('group_key', 36)->nullable()->after('note');
            // Tipo di raggruppamento (NULL = esercizio standalone)
            $table->enum('group_type', ['superset', 'giant_set', 'circuit'])->nullable()->after('group_key');
        });
    }

    public function down(): void
    {
        Schema::table('template_session_exercises', function (Blueprint $table) {
            $table->dropColumn(['group_key', 'group_type']);
        });
    }
};
