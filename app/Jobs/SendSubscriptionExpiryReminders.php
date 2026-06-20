<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Notifications\SubscriptionExpiryNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SendSubscriptionExpiryReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $today = Carbon::today();

        foreach ([7, 2] as $days) {
            $expiryDate = $today->copy()->addDays($days)->toDateString();

            Subscription::active()
                ->where('expires_at', $expiryDate)
                ->with('member.user')
                ->each(function (Subscription $subscription) use ($days) {
                    $member = $subscription->member;
                    if ($member?->user !== null) {
                        $member->user->notify(new SubscriptionExpiryNotification($member, $days));
                    }
                });
        }
    }
}
