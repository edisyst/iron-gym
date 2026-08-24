<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Passo 1: aggiunge nuove colonne come nullable (DDL)
        Schema::table('class_bookings', function (Blueprint $table) {
            $table->foreignId('class_occurrence_id')->nullable()->constrained('class_occurrences')->cascadeOnDelete()->after('id');
            $table->timestamp('attended_at')->nullable();
            $table->foreignId('booked_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // Passo 2: popola class_occurrence_id usando old_class_id su class_occurrences (DML)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                UPDATE class_bookings cb
                INNER JOIN class_occurrences co ON co.old_class_id = cb.class_id
                SET cb.class_occurrence_id = co.id
            ');
        } else {
            DB::statement('
                UPDATE class_bookings
                SET class_occurrence_id = (
                    SELECT id FROM class_occurrences
                    WHERE class_occurrences.old_class_id = class_bookings.class_id
                    LIMIT 1
                )
            ');
        }

        // Passo 3: mappa lo status cancelled → cancelled_by_athlete (DML)
        DB::statement("UPDATE class_bookings SET status = 'cancelled_by_athlete' WHERE status = 'cancelled'");

        // Passo 4: aggiorna l'enum con i nuovi valori (DDL — MySQL only; SQLite usa TEXT)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE class_bookings MODIFY COLUMN status
                ENUM('confirmed','waitlisted','cancelled_by_athlete','cancelled_by_gym','no_show')
                NOT NULL DEFAULT 'confirmed'");
        }

        // Passo 5: rende class_occurrence_id NOT NULL (DDL)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE class_bookings MODIFY class_occurrence_id BIGINT UNSIGNED NOT NULL');
        }

        // Passo 6: rimuove old_class_id da class_occurrences (colonna helper di migrazione) (DDL)
        Schema::table('class_occurrences', function (Blueprint $table) {
            $table->dropColumn('old_class_id');
        });

        // Passo 7: rimuove class_id e i vecchi constraint (DDL)
        // Nota: il FK va droppato prima dell'indice perché MySQL usa idx_class_booking_status
        // per supportare il vincolo di FK su class_id.
        Schema::table('class_bookings', function (Blueprint $table) {
            $table->dropUnique('uq_class_member');
            $table->dropForeign(['class_id']);
            $table->dropIndex('idx_class_booking_status');
            $table->dropColumn('class_id');
        });

        // Passo 8: aggiunge il nuovo unique constraint e indice (DDL)
        Schema::table('class_bookings', function (Blueprint $table) {
            $table->unique(['class_occurrence_id', 'member_id'], 'uq_occurrence_member');
            $table->index(['class_occurrence_id', 'status'], 'idx_booking_occurrence_status');
        });
    }

    public function down(): void
    {
        // Passo 1: rimuove FK e constraint (DDL)
        // Il FK su class_occurrence_id va droppato prima dell'indice idx_booking_occurrence_status
        // che MySQL usa per supportarlo.
        Schema::table('class_bookings', function (Blueprint $table) {
            $table->dropForeign(['class_occurrence_id']);
            $table->dropUnique('uq_occurrence_member');
            $table->dropIndex('idx_booking_occurrence_status');
        });

        // Passo 2: ri-aggiunge class_id come nullable (DDL)
        Schema::table('class_bookings', function (Blueprint $table) {
            $table->foreignId('class_id')->nullable()->constrained('group_classes')->cascadeOnDelete()->after('id');
        });

        // Passo 3: ri-aggiunge old_class_id su class_occurrences (DDL)
        Schema::table('class_occurrences', function (Blueprint $table) {
            $table->unsignedBigInteger('old_class_id')->nullable();
        });

        // Passo 4: popola old_class_id = group_class_id (best-effort) (DML)
        DB::statement('UPDATE class_occurrences SET old_class_id = group_class_id');

        // Passo 5: popola class_id usando old_class_id (DML)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                UPDATE class_bookings cb
                INNER JOIN class_occurrences co ON co.id = cb.class_occurrence_id
                SET cb.class_id = co.old_class_id
            ');
        } else {
            DB::statement('
                UPDATE class_bookings
                SET class_id = (
                    SELECT old_class_id FROM class_occurrences
                    WHERE class_occurrences.id = class_bookings.class_occurrence_id
                    LIMIT 1
                )
            ');
        }

        // Passo 6: ripristina la enum originale (DDL + DML)
        DB::statement("UPDATE class_bookings SET status = 'cancelled' WHERE status IN ('cancelled_by_athlete','cancelled_by_gym','no_show')");
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE class_bookings MODIFY COLUMN status
                ENUM('confirmed','waitlisted','cancelled')
                NOT NULL DEFAULT 'confirmed'");
        }

        // Passo 7: rimuove le nuove colonne (DDL)
        Schema::table('class_bookings', function (Blueprint $table) {
            $table->dropForeign(['booked_by']);
            $table->dropColumn(['class_occurrence_id', 'attended_at', 'booked_by']);
        });

        // Passo 7b: elimina righe duplicate (class_id, member_id) prima del vincolo unique (DML)
        // Più occorrenze per stesso corso possono generare duplicati durante il rollback.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                DELETE cb1 FROM class_bookings cb1
                INNER JOIN class_bookings cb2
                ON cb1.class_id = cb2.class_id AND cb1.member_id = cb2.member_id AND cb1.id > cb2.id
            ');
        } else {
            DB::statement('
                DELETE FROM class_bookings WHERE id NOT IN (
                    SELECT min_id FROM (
                        SELECT MIN(id) as min_id FROM class_bookings GROUP BY class_id, member_id
                    ) t
                )
            ');
        }

        // Passo 8: ri-aggiunge vecchi constraint (DDL)
        Schema::table('class_bookings', function (Blueprint $table) {
            $table->unique(['class_id', 'member_id'], 'uq_class_member');
            $table->index(['class_id', 'status'], 'idx_class_booking_status');
        });
    }
};
