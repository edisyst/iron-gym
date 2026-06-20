<?php

namespace App\Notifications;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MedicalCertExpiryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Member $member) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $date = $this->member->medical_cert_expiry?->format('d/m/Y') ?? 'N/D';

        return (new MailMessage)
            ->subject("Il tuo certificato medico scade il {$date}")
            ->greeting("Ciao {$this->member->first_name},")
            ->line("Il tuo certificato medico scade il **{$date}**.")
            ->line('Ricordati di portare il rinnovo in palestra per continuare ad allenarti senza interruzioni.')
            ->salutation('Il team Iron Gym');
    }

    /** @return array{type: string, member_id: int, message: string} */
    public function toArray(mixed $notifiable): array
    {
        $date = $this->member->medical_cert_expiry?->format('d/m/Y') ?? 'N/D';

        return [
            'type' => 'medical_cert_expiry',
            'member_id' => $this->member->id,
            'message' => "Certificato medico in scadenza il {$date}",
        ];
    }
}
