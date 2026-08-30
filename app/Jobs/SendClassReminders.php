<?php

namespace App\Jobs;

use App\Models\ClassOccurrence;
use App\Models\Setting;
use App\Notifications\ClassReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SendClassReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        if (! Setting::bool('outbound_notifications_enabled', true)) {
            Log::warning('[outbound_notifications] invio soppresso da interruttore', ['job' => static::class]);

            return;
        }

        $tomorrow = Carbon::tomorrow()->toDateString();

        ClassOccurrence::query()
            ->whereDate('date', $tomorrow)
            ->where('status', 'planned')
            ->with(['groupClass', 'confirmedBookings.member.user'])
            ->each(function (ClassOccurrence $occurrence) {
                foreach ($occurrence->confirmedBookings as $booking) {
                    $user = $booking->member?->user;
                    if ($user !== null) {
                        $user->notify(new ClassReminderNotification($occurrence));
                    }
                }
            });
    }
}
