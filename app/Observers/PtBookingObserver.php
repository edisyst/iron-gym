<?php

namespace App\Observers;

use App\Models\PtBooking;
use Illuminate\Support\Facades\Cache;

class PtBookingObserver
{
    public function saved(PtBooking $booking): void
    {
        $this->invalidate($booking);
    }

    public function deleted(PtBooking $booking): void
    {
        $this->invalidate($booking);
    }

    private function invalidate(PtBooking $booking): void
    {
        $date = $booking->booked_date->toDateString();

        Cache::forget("slots:{$booking->trainer_id}:{$date}");
        Cache::tags(['kpi'])->flush();
    }
}
