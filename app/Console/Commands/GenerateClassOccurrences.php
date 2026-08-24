<?php

namespace App\Console\Commands;

use App\Models\ClassOccurrence;
use App\Models\ClassSchedule;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;

class GenerateClassOccurrences extends Command
{
    protected $signature = 'classes:generate-occurrences
                            {--horizon= : Giorni da oggi da coprire (default: config classes.generation_horizon_days)}';

    protected $description = 'Materializza ClassOccurrence dal palinsesto ClassSchedule per i prossimi N giorni (idempotente).';

    public function handle(): int
    {
        $horizon = (int) ($this->option('horizon') ?? config('classes.generation_horizon_days', 28));
        $today = Carbon::today();
        $until = $today->copy()->addDays($horizon - 1);

        $schedules = ClassSchedule::with('groupClass')
            ->where('is_active', true)
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($schedules as $schedule) {
            // Inizio periodo: max(oggi, valid_from) — non generare prima della validità
            $periodStart = ($schedule->valid_from !== null && $schedule->valid_from->gt($today))
                ? $schedule->valid_from->copy()
                : $today->copy();

            // Fine periodo: min(until, valid_until) — non generare oltre la scadenza
            $periodEnd = ($schedule->valid_until !== null && $schedule->valid_until->lt($until))
                ? $schedule->valid_until->copy()
                : $until->copy();

            if ($periodStart->gt($periodEnd)) {
                continue;
            }

            $endTime = Carbon::createFromFormat('H:i:s', $schedule->start_time)
                ->addMinutes($schedule->groupClass->duration_minutes)
                ->format('H:i:s');

            foreach (CarbonPeriod::create($periodStart, $periodEnd) as $day) {
                // weekday: 0=lun..6=dom; Carbon dayOfWeekIso: 1=lun..7=dom
                if (($day->dayOfWeekIso - 1) !== $schedule->weekday) {
                    continue;
                }

                $occurrence = ClassOccurrence::firstOrCreate(
                    [
                        'class_schedule_id' => $schedule->id,
                        'date' => $day->copy()->startOfDay(),
                    ],
                    [
                        'group_class_id' => $schedule->group_class_id,
                        'start_time' => $schedule->start_time,
                        'end_time' => $endTime,
                        'trainer_id' => $schedule->trainer_id,
                        'capacity' => $schedule->groupClass->default_capacity,
                        'status' => 'planned',
                    ]
                );

                if ($occurrence->wasRecentlyCreated) {
                    $created++;
                } else {
                    $skipped++;
                }
            }
        }

        $this->info("Occorrenze create: {$created} | già esistenti: {$skipped}.");

        return self::SUCCESS;
    }
}
