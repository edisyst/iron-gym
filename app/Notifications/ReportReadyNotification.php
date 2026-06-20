<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReportReadyNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $reportName,
        public readonly string $downloadRoute,
    ) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    /** @return array{type: string, report_name: string, download_route: string, message: string} */
    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'report_ready',
            'report_name' => $this->reportName,
            'download_route' => $this->downloadRoute,
            'message' => "Report \"{$this->reportName}\" pronto per il download.",
        ];
    }
}
