<?php

namespace App\Http\Controllers;

use App\Http\Requests\SyncBatchRequest;
use App\Models\ExerciseSet;
use App\Models\SessionExercise;
use App\Models\SyncOperation;
use App\Services\PersonalRecordDetector;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SyncBatchController extends Controller
{
    public function handle(SyncBatchRequest $request): JsonResponse
    {
        $athleteId = Auth::id();
        $results = [];

        foreach ($request->validated('operations') as $op) {
            $uuid = $op['client_uuid'];

            if (SyncOperation::where('client_uuid', $uuid)->exists()) {
                $results[] = ['client_uuid' => $uuid, 'status' => 'skipped'];

                continue;
            }

            $status = DB::transaction(function () use ($op, $uuid, $athleteId) {
                $status = match ($op['operation']) {
                    'quick_log' => $this->applyQuickLog($op, $athleteId),
                    'complete_set' => $this->applyCompleteSet($op, $athleteId),
                    'generate_warmup' => $this->applyGenerateWarmup($op, $athleteId),
                    'delete_warmup' => $this->applyDeleteWarmup($op, $athleteId),
                    default => 'error',
                };

                if ($status !== 'error') {
                    SyncOperation::create(['client_uuid' => $uuid, 'operation' => $op['operation']]);
                }

                return $status;
            });

            $results[] = ['client_uuid' => $uuid, 'status' => $status];
        }

        return response()->json(['results' => $results]);
    }

    /** @param array<string, mixed> $op */
    private function applyQuickLog(array $op, int $athleteId): string
    {
        $set = $this->findOwnedSet($op['payload']['set_id'], $athleteId);
        if ($set === null) {
            return 'forbidden';
        }

        if ($this->isStale($set, $op['client_timestamp'])) {
            return 'skipped_conflict';
        }

        $measurementType = $set->sessionExercise->exercise->measurement_type;

        $updates = match ($measurementType) {
            'reps_weight', 'time_weight' => [
                'actual_reps' => $set->planned_reps,
                'actual_weight_kg' => $set->planned_weight_kg,
                'actual_rir' => $set->planned_rir,
            ],
            'reps_only' => [
                'actual_reps' => $set->planned_reps,
                'actual_rir' => $set->planned_rir,
            ],
            'time', 'isometric_hold' => [
                'actual_duration_sec' => $set->planned_duration_sec,
            ],
            default => [],
        };

        if ($set->completed_at === null) {
            $updates['completed_at'] = now();
        }

        $set->update($updates);
        $set->refresh();
        app(PersonalRecordDetector::class)->check($set, $athleteId);

        return 'ok';
    }

    /** @param array<string, mixed> $op */
    private function applyCompleteSet(array $op, int $athleteId): string
    {
        $set = $this->findOwnedSet($op['payload']['set_id'], $athleteId);
        if ($set === null) {
            return 'forbidden';
        }

        if ($this->isStale($set, $op['client_timestamp'])) {
            return 'skipped_conflict';
        }

        $payload = $op['payload'];
        $updates = [
            'actual_reps' => isset($payload['reps']) ? (int) $payload['reps'] : null,
            'actual_weight_kg' => isset($payload['weight']) ? (float) $payload['weight'] : null,
            'actual_rir' => isset($payload['rir']) ? (int) $payload['rir'] : null,
            'actual_duration_sec' => isset($payload['duration']) ? (int) $payload['duration'] : null,
        ];

        if ($set->completed_at === null) {
            $updates['completed_at'] = now();
        }

        $set->update($updates);
        $set->refresh();
        app(PersonalRecordDetector::class)->check($set, $athleteId);

        return 'ok';
    }

    /** @param array<string, mixed> $op */
    private function applyGenerateWarmup(array $op, int $athleteId): string
    {
        $seId = $op['payload']['session_exercise_id'];

        $se = SessionExercise::whereHas(
            'session.week.mesocycle',
            fn ($q) => $q->where('athlete_id', $athleteId)
        )->find($seId);

        if ($se === null) {
            return 'forbidden';
        }

        $alreadyHasWarmup = ExerciseSet::where('session_exercise_id', $seId)
            ->where('is_warmup', true)
            ->exists();

        if ($alreadyHasWarmup) {
            return 'skipped';
        }

        $firstWorking = ExerciseSet::where('session_exercise_id', $seId)
            ->where('is_warmup', false)
            ->orderBy('set_index')
            ->first();

        if ($firstWorking === null || $firstWorking->planned_weight_kg === null) {
            return 'skipped';
        }

        $target = (float) $firstWorking->planned_weight_kg;
        $warmupDef = $target >= 40
            ? [[0.50, 8], [0.70, 5], [0.85, 3]]
            : [[0.50, 8]];

        $warmupCount = count($warmupDef);

        ExerciseSet::where('session_exercise_id', $seId)
            ->where('is_warmup', false)
            ->orderByDesc('set_index')
            ->get()
            ->each(fn ($s) => $s->update(['set_index' => $s->set_index + $warmupCount]));

        foreach ($warmupDef as $i => [$pct, $reps]) {
            $weight = round($target * $pct / 2.5) * 2.5;
            ExerciseSet::create([
                'session_exercise_id' => $seId,
                'set_index' => $i + 1,
                'is_warmup' => true,
                'planned_reps' => $reps,
                'planned_weight_kg' => $weight,
                'planned_rir' => null,
                'planned_duration_sec' => null,
            ]);
        }

        return 'ok';
    }

    /** @param array<string, mixed> $op */
    private function applyDeleteWarmup(array $op, int $athleteId): string
    {
        $set = $this->findOwnedSet($op['payload']['set_id'], $athleteId);
        if ($set === null) {
            return 'forbidden';
        }

        if (! $set->is_warmup) {
            return 'error';
        }

        $set->delete();

        return 'ok';
    }

    private function findOwnedSet(int $setId, int $athleteId): ?ExerciseSet
    {
        return ExerciseSet::whereHas(
            'sessionExercise.session.week.mesocycle',
            fn ($q) => $q->where('athlete_id', $athleteId)
        )->find($setId);
    }

    /**
     * Ultimo aggiornamento del set (completed_at) è più recente del timestamp client.
     * In quel caso il record server vince (last-write-wins).
     */
    private function isStale(ExerciseSet $set, int $clientTimestampMs): bool
    {
        if ($set->completed_at === null) {
            return false;
        }

        return Carbon::parse((string) $set->completed_at)->timestamp * 1000 > $clientTimestampMs;
    }
}
