<?php

namespace App\Notifications;

use App\Models\ClassOccurrence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClassOccurrenceCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ClassOccurrence $occurrence) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['mail', 'database', 'webpush'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $name = $this->occurrence->groupClass->name;
        $date = $this->occurrence->date->format('d/m/Y').' '.substr($this->occurrence->start_time, 0, 5);

        return (new MailMessage)
            ->subject("Corso {$name} del {$date} cancellato")
            ->greeting('Avviso importante')
            ->line("Il corso **{$name}** previsto per il **{$date}** è stato cancellato.")
            ->line('Ci scusiamo per il disagio.')
            ->salutation('Il team Iron Gym');
    }

    /** @return array{type: string, occurrence_id: int, message: string} */
    public function toArray(mixed $notifiable): array
    {
        $name = $this->occurrence->groupClass->name;
        $date = $this->occurrence->date->format('d/m/Y').' '.substr($this->occurrence->start_time, 0, 5);

        return [
            'type' => 'class_cancelled',
            'occurrence_id' => $this->occurrence->id,
            'message' => "Corso {$name} del {$date} cancellato",
        ];
    }

    /** @return array{title: string, body: string} */
    public function toWebPush(mixed $notifiable, mixed $notification): array
    {
        $name = $this->occurrence->groupClass->name;
        $date = $this->occurrence->date->format('d/m/Y').' '.substr($this->occurrence->start_time, 0, 5);

        return [
            'title' => 'Corso cancellato',
            'body' => "{$name} del {$date} è stato cancellato",
        ];
    }
}
