<?php

namespace App\Notifications;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Member $member,
        public readonly int $daysLeft,
    ) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Il tuo abbonamento scade tra {$this->daysLeft} giorni")
            ->greeting("Ciao {$this->member->first_name},")
            ->line("Il tuo abbonamento scade tra **{$this->daysLeft} giorni**.")
            ->line('Rinnova subito per non interrompere i tuoi allenamenti.')
            ->salutation('Il team Iron Gym');
    }

    /** @return array{type: string, member_id: int, days_left: int, message: string} */
    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'subscription_expiry',
            'member_id' => $this->member->id,
            'days_left' => $this->daysLeft,
            'message' => "Abbonamento in scadenza tra {$this->daysLeft} giorni",
        ];
    }
}
