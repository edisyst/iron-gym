<?php

namespace App\Exports;

use App\Models\Subscription;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriptionCsvExporter
{
    public function __construct(private readonly string $filter = 'all') {}

    public function stream(): StreamedResponse
    {
        $subscriptions = Subscription::with(['member', 'plan'])
            ->when($this->filter === 'active', fn ($q) => $q->active())
            ->when($this->filter === 'expired', fn ($q) => $q->where('status', 'expired'))
            ->when($this->filter === 'expiring', fn ($q) => $q->expiringSoon(30))
            ->when($this->filter === 'suspended', fn ($q) => $q->where('status', 'suspended'))
            ->orderByDesc('created_at')
            ->get();

        $filename = 'abbonamenti-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($subscriptions): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Cognome', 'Nome', 'Email', 'Piano', 'Inizio', 'Scadenza', 'Stato'], ';');

            foreach ($subscriptions as $sub) {
                fputcsv($handle, [
                    $sub->member->last_name,
                    $sub->member->first_name,
                    $sub->member->email,
                    $sub->plan->name,
                    $sub->started_at->format('d/m/Y'),
                    $sub->expires_at->format('d/m/Y'),
                    $sub->status,
                ], ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
