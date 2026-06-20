<?php

namespace App\Notifications;

use App\Models\TrainingSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SessionReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly TrainingSession $session) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['database', 'webpush'];
    }

    /** @return array{type: string, session_id: int, message: string} */
    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'session_reminder',
            'session_id' => $this->session->id,
            'message' => "Hai una sessione programmata oggi: {$this->session->name}",
        ];
    }

    /** @return array{title: string, body: string} */
    public function toWebPush(mixed $notifiable, mixed $notification): array
    {
        return [
            'title' => 'Sessione oggi',
            'body' => "Hai una sessione programmata oggi: {$this->session->name}",
        ];
    }
}
