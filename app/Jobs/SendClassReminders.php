<?php

namespace App\Jobs;

use App\Models\ClassOccurrence;
use App\Notifications\ClassReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SendClassReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
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
