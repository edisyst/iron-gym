<?php

namespace App\Jobs;

use App\Models\ClassBooking;
use App\Models\ClassOccurrence;
use App\Models\Setting;
use App\Notifications\ClassOccurrenceCancelledNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyClassCancellation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly ClassOccurrence $occurrence) {}

    public function handle(): void
    {
        if (! Setting::bool('outbound_notifications_enabled', true)) {
            return;
        }

        $notification = new ClassOccurrenceCancelledNotification($this->occurrence);

        $this->occurrence->confirmedBookings()
            ->with('member.user')
            ->each(function (ClassBooking $booking) use ($notification) {
                if ($booking->member?->user !== null) {
                    $booking->member->user->notify($notification);
                }
            });
    }
}
