<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Message $message) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    /** @return array{type: string, message_id: int, sender_name: string, preview: string} */
    public function toArray(mixed $notifiable): array
    {
        $sender = $this->message->sender;

        return [
            'type' => 'new_message',
            'message_id' => $this->message->id,
            'sender_name' => $sender !== null ? $sender->name : 'Utente',
            'preview' => mb_substr($this->message->body, 0, 80),
        ];
    }
}
