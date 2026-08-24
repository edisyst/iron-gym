<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Passo 1: aggiunge nuove colonne come nullable (DDL)
        Schema::table('group_classes', function (Blueprint $table) {
            $table->string('slug', 128)->nullable()->after('id');
            $table->tinyInteger('default_capacity')->unsigned()->nullable()->after('description');
            $table->string('room', 64)->nullable()->after('default_capacity');
            $table->string('color', 7)->nullable()->after('room');
            $table->boolean('is_active')->default(true)->after('color');
        });

        // Passo 2: deduplica definizioni con stesso name e popola slug + default_capacity (DML)
        $seenNames = [];
        $oldIdToNewId = [];

        $rows = DB::table('group_classes')->orderBy('id')->get();

        foreach ($rows as $row) {
            $baseSlug = Str::slug($row->name);
            $normalizedName = strtolower(trim($row->name));

            if (isset($seenNames[$normalizedName])) {
                $oldIdToNewId[$row->id] = $seenNames[$normalizedName]['canonical_id'];
            } else {
                $slug = $baseSlug;
                $suffix = 1;
                $usedSlugs = array_column($seenNames, 'slug');
                while (in_array($slug, $usedSlugs, true)) {
                    $slug = $baseSlug.'-'.(++$suffix);
                }

                DB::table('group_classes')->where('id', $row->id)->update([
                    'slug' => $slug,
                    'default_capacity' => $row->max_participants,
                ]);

                $seenNames[$normalizedName] = [
                    'canonical_id' => $row->id,
                    'slug' => $slug,
                ];
                $oldIdToNewId[$row->id] = $row->id;
            }
        }

        // Passo 3: crea class_trainer e class_occurrences per ogni riga originale (DML)
        foreach ($rows as $row) {
            $canonicalId = $oldIdToNewId[$row->id];

            DB::table('class_trainer')->upsert(
                [['group_class_id' => $canonicalId, 'trainer_id' => $row->trainer_id]],
                ['group_class_id', 'trainer_id']
            );

            $status = match ($row->status) {
                'completed' => 'completed',
                'cancelled' => 'cancelled',
                default => 'planned',
            };

            $scheduledAt = Carbon::parse($row->scheduled_at);
            $endTime = $scheduledAt->copy()->addMinutes($row->duration_minutes)->format('H:i:s');

            DB::table('class_occurrences')->insert([
                'group_class_id' => $canonicalId,
                'class_schedule_id' => null,
                'date' => $scheduledAt->toDateString(),
                'start_time' => $scheduledAt->format('H:i:s'),
                'end_time' => $endTime,
                'trainer_id' => $row->trainer_id,
                'capacity' => $row->max_participants,
                'status' => $status,
                'cancellation_reason' => $row->cancellation_reason,
                'old_class_id' => $row->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Passo 4: elimina righe duplicate (DML)
        $duplicateIds = [];
        foreach ($oldIdToNewId as $oldId => $canonicalId) {
            if ($oldId !== $canonicalId) {
                $duplicateIds[] = $oldId;
            }
        }
        if (! empty($duplicateIds)) {
            DB::table('group_classes')->whereIn('id', $duplicateIds)->delete();
        }

        // Passo 5: rende slug e default_capacity NOT NULL (DDL)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE group_classes MODIFY slug VARCHAR(128) NOT NULL');
            DB::statement('ALTER TABLE group_classes MODIFY default_capacity TINYINT UNSIGNED NOT NULL');
        }

        // Passo 6: rimuove le vecchie colonne e indici (DDL)
        Schema::table('group_classes', function (Blueprint $table) {
            $table->dropForeign(['trainer_id']);
            $table->dropIndex('idx_class_scheduled');
            $table->dropIndex('idx_class_status');
            $table->dropColumn([
                'trainer_id',
                'scheduled_at',
                'max_participants',
                'status',
                'cancellation_reason',
            ]);
        });

        // Passo 7: aggiunge indice univoco su slug (DDL)
        Schema::table('group_classes', function (Blueprint $table) {
            $table->unique('slug', 'uq_group_class_slug');
        });
    }

    public function down(): void
    {
        // Passo 1: rimuove il vincolo unique su slug (DDL)
        Schema::table('group_classes', function (Blueprint $table) {
            $table->dropUnique('uq_group_class_slug');
        });

        // Passo 2: ri-aggiunge le colonne rimosse come nullable (DDL)
        Schema::table('group_classes', function (Blueprint $table) {
            $table->foreignId('trainer_id')->nullable()->constrained('users')->restrictOnDelete()->after('id');
            $table->dateTime('scheduled_at')->nullable()->after('description');
            $table->tinyInteger('max_participants')->unsigned()->nullable()->after('duration_minutes');
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled')->after('max_participants');
            $table->text('cancellation_reason')->nullable()->after('status');
        });

        // Passo 3: popola le colonne dalla prima occorrenza per ogni corso (DML)
        $occurrences = DB::table('class_occurrences')->orderBy('id')->get();
        $processed = [];

        foreach ($occurrences as $occ) {
            if (isset($processed[$occ->group_class_id])) {
                continue;
            }
            $processed[$occ->group_class_id] = true;

            $statusBack = match ($occ->status) {
                'completed' => 'completed',
                'cancelled' => 'cancelled',
                default => 'scheduled',
            };

            DB::table('group_classes')->where('id', $occ->group_class_id)->update([
                'trainer_id' => $occ->trainer_id,
                'scheduled_at' => $occ->date.' '.substr($occ->start_time, 0, 5),
                'max_participants' => $occ->capacity,
                'status' => $statusBack,
                'cancellation_reason' => $occ->cancellation_reason,
            ]);
        }

        // Passo 4: rimuove le nuove colonne (DDL)
        Schema::table('group_classes', function (Blueprint $table) {
            $table->dropColumn(['slug', 'default_capacity', 'room', 'color', 'is_active']);
        });

        // Passo 5: ri-aggiunge gli indici originali (DDL)
        Schema::table('group_classes', function (Blueprint $table) {
            $table->index('scheduled_at', 'idx_class_scheduled');
            $table->index('status', 'idx_class_status');
        });

        // Passo 6: svuota class_trainer e class_occurrences (DML — le tabelle cadono con le proprie migration)
        DB::table('class_trainer')->delete();
        DB::table('class_occurrences')->delete();
    }
};
