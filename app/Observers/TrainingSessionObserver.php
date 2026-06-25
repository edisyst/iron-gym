<?php

namespace App\Observers;

use App\Models\TrainingSession;
use App\Services\WeeklyVolumeCalculator;
use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;

class TrainingSessionObserver
{
    public function updated(TrainingSession $session): void
    {
        if ($session->isDirty('status') && $session->status === 'completed') {
            $this->invalidateVolumeCache($session);
        }
    }

    private function invalidateVolumeCache(TrainingSession $session): void
    {
        $weekId = $session->microcycle_week_id;

        // Carica il mesociclo per ricavare athlete_id
        $week = $session->week()->with('mesocycle')->first();
        if ($week === null || $week->mesocycle === null) {
            return;
        }

        app(WeeklyVolumeCalculator::class)->forget($week->mesocycle->athlete_id, $weekId);

        // Invalida anche i KPI che includono sessioni completate
        if (Cache::getStore() instanceof TaggableStore) {
            Cache::tags(['kpi'])->flush();
        }
    }
}
