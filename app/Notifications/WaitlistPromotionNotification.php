<?php

namespace App\Notifications;

use App\Models\GroupClass;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WaitlistPromotionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly GroupClass $groupClass) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['mail', 'database', 'webpush'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $date = $this->groupClass->scheduled_at->format('d/m/Y H:i');

        return (new MailMessage)
            ->subject("Sei stato confermato per il corso {$this->groupClass->name}")
            ->greeting('Ottima notizia!')
            ->line("Sei stato confermato per il corso **{$this->groupClass->name}** del **{$date}**.")
            ->line('Ti aspettiamo in palestra!')
            ->salutation('Il team Iron Gym');
    }

    /** @return array{type: string, class_id: int, message: string} */
    public function toArray(mixed $notifiable): array
    {
        $date = $this->groupClass->scheduled_at->format('d/m/Y H:i');

        return [
            'type' => 'waitlist_promotion',
            'class_id' => $this->groupClass->id,
            'message' => "Confermato per il corso {$this->groupClass->name} del {$date}",
        ];
    }

    /** @return array{title: string, body: string} */
    public function toWebPush(mixed $notifiable, mixed $notification): array
    {
        $date = $this->groupClass->scheduled_at->format('d/m/Y H:i');

        return [
            'title' => 'Posto confermato!',
            'body' => "Sei confermato per {$this->groupClass->name} del {$date}",
        ];
    }
}
