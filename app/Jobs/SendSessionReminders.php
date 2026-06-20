<?php

namespace App\Jobs;

use App\Models\TrainingSession;
use App\Notifications\SessionReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SendSessionReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $today = Carbon::today()->toDateString();

        TrainingSession::query()
            ->where('scheduled_date', $today)
            ->where('status', 'planned')
            ->whereNull('started_at')
            ->with('week.mesocycle.athlete')
            ->each(function (TrainingSession $session) {
                $athlete = $session->week?->mesocycle?->athlete;
                if ($athlete !== null) {
                    $athlete->notify(new SessionReminderNotification($session));
                }
            });
    }
}
