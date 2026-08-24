<?php

namespace App\Notifications;

use App\Models\ClassOccurrence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ClassReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ClassOccurrence $occurrence) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['database', 'webpush'];
    }

    /** @return array{type: string, occurrence_id: int, message: string} */
    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'class_reminder',
            'occurrence_id' => $this->occurrence->id,
            'message' => 'Domani hai '.$this->occurrence->groupClass->name.
                ' alle '.substr($this->occurrence->start_time, 0, 5).'.',
        ];
    }

    /** @return array{title: string, body: string} */
    public function toWebPush(mixed $notifiable, mixed $notification): array
    {
        return [
            'title' => 'Corso domani',
            'body' => 'Domani hai '.$this->occurrence->groupClass->name.
                ' alle '.substr($this->occurrence->start_time, 0, 5).'.',
        ];
    }
}
