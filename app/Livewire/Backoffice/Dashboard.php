<?php

namespace App\Livewire\Backoffice;

use App\Models\AccessLog;
use App\Models\Member;
use App\Models\Subscription;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public int $activeMembersCount = 0;

    public int $expiringSoonCount = 0;

    public int $accessesTodayCount = 0;

    public int $medicalCertIssuesCount = 0;

    public function mount(): void
    {
        $this->activeMembersCount = Member::where('is_active', true)->count();

        $this->expiringSoonCount = Subscription::expiringSoon(30)->count();

        $this->accessesTodayCount = AccessLog::whereDate('checked_in_at', today())->count();

        // Tesserati attivi senza certificato o con cert in scadenza entro 30 giorni
        $this->medicalCertIssuesCount = Member::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('medical_cert_expiry')
                    ->orWhere('medical_cert_expiry', '<=', now()->addDays(30)->toDateString());
            })
            ->count();
    }

    public function render(): View
    {
        return view('livewire.backoffice.dashboard')
            ->layout('layouts.backoffice')
            ->layoutData(['page_title' => 'Dashboard']);
    }
}
