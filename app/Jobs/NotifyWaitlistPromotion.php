<?php

namespace App\Jobs;

use App\Models\ClassBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
        // Step 7: implementazione notifica multicanale (mail, push, SMS)
    }
}
