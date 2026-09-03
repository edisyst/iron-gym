<?php

namespace App\Exports;

use App\Models\Member;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberCsvExporter
{
    public function __construct(
        private readonly string $search = '',
        private readonly string $certFilter = '',
    ) {}

    public function stream(): StreamedResponse
    {
        $members = Member::with(['activeSubscription.plan'])
            ->when($this->search, fn ($q) => $q->where(function ($q2): void {
                $q2->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->when($this->certFilter === 'missing', fn ($q) => $q->whereNull('medical_cert_expiry'))
            ->when($this->certFilter === 'expired', fn ($q) => $q->whereNotNull('medical_cert_expiry')->where('medical_cert_expiry', '<', now()))
            ->when($this->certFilter === 'expiring_soon', fn ($q) => $q->whereNotNull('medical_cert_expiry')->whereBetween('medical_cert_expiry', [now(), now()->addDays(30)]))
            ->orderBy('last_name')->orderBy('first_name')
            ->get();

        $filename = 'tesserati-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($members): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Cognome', 'Nome', 'Email', 'Telefono', 'Abbonamento', 'Scadenza abb.', 'Cert. medico', 'Attivo'], ';');

            foreach ($members as $member) {
                $sub = $member->activeSubscription;
                fputcsv($handle, [
                    $member->last_name,
                    $member->first_name,
                    $member->email,
                    $member->phone ?? '',
                    $sub?->plan->name ?? '',
                    $sub?->expires_at->format('d/m/Y') ?? '',
                    $member->medical_cert_expiry?->format('d/m/Y') ?? '',
                    $member->is_active ? 'Si' : 'No',
                ], ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
