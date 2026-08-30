<?php

namespace App\Jobs;

use App\Models\ClassBooking;
use App\Models\Setting;
use App\Notifications\WaitlistPromotionNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Notifica all'atleta la promozione da waitlist a confirmed per un corso collettivo.
 * Implementazione notifica rinviata allo Step 7.
 */
class NotifyWaitlistPromotion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly ClassBooking $booking) {}

    public function handle(): void
    {
        if (! Setting::bool('outbound_notifications_enabled', true)) {
            Log::warning('[outbound_notifications] invio soppresso da interruttore', ['job' => static::class]);

            return;
        }

        $member = $this->booking->member;
        if ($member?->user !== null) {
            $member->user->notify(new WaitlistPromotionNotification($this->booking->occurrence));
        }
    }
}
