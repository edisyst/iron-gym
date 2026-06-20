<?php

namespace App\Observers;

use App\Models\TrainerAvailability;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class TrainerAvailabilityObserver
{
    public function saved(TrainerAvailability $availability): void
    {
        $rawDate = $availability->specific_date;
        $dateStr = $rawDate !== null ? Carbon::parse($rawDate)->toDateString() : null;
        $this->invalidate($availability->trainer_id, $dateStr);
    }

    public function deleted(TrainerAvailability $availability): void
    {
        $rawDate = $availability->specific_date;
        $dateStr = $rawDate !== null ? Carbon::parse($rawDate)->toDateString() : null;
        $this->invalidate($availability->trainer_id, $dateStr);
    }

    private function invalidate(int $trainerId, ?string $date): void
    {
        if ($date !== null) {
            Cache::forget("slots:{$trainerId}:{$date}");
        }

        Cache::tags(['kpi'])->flush();
    }
}
