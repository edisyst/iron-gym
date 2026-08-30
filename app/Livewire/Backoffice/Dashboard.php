<?php

namespace App\Livewire\Backoffice;

use App\Models\AccessLog;
use App\Models\Member;
use App\Models\Subscription;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public int $activeMembersCount = 0;

    public int $expiringSoonCount = 0;

    public int $accessesTodayCount = 0;

    public int $medicalCertIssuesCount = 0;

    public int $certExpiring30Count = 0;

    public int $subExpiring7Count = 0;

    public function mount(): void
    {
        $counts = Cache::remember('backoffice_dashboard_counts', 300, function () {
            $activeMembersCount = Member::where('is_active', true)->count();
            $expiringSoonCount = Subscription::expiringSoon(30)->count();
            $subExpiring7Count = Subscription::expiringSoon(7)->count();

            $medicalCertIssuesCount = Member::where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('medical_cert_expiry')
                        ->orWhere('medical_cert_expiry', '<=', now()->addDays(30)->toDateString());
                })
                ->count();

            $certExpiring30Count = Member::where('is_active', true)
                ->whereNotNull('medical_cert_expiry')
                ->whereBetween('medical_cert_expiry', [today()->toDateString(), now()->addDays(30)->toDateString()])
                ->count();

            return compact(
                'activeMembersCount',
                'expiringSoonCount',
                'subExpiring7Count',
                'medicalCertIssuesCount',
                'certExpiring30Count',
            );
        });

        $this->activeMembersCount = $counts['activeMembersCount'];
        $this->expiringSoonCount = $counts['expiringSoonCount'];
        $this->subExpiring7Count = $counts['subExpiring7Count'];
        $this->medicalCertIssuesCount = $counts['medicalCertIssuesCount'];
        $this->certExpiring30Count = $counts['certExpiring30Count'];

        if (auth()->user()->can('view-access-logs')) {
            $this->accessesTodayCount = AccessLog::whereDate('checked_in_at', today())->count();
        }
    }

    public function render(): View
    {
        return view('livewire.backoffice.dashboard')
            ->layout('layouts.backoffice')
            ->layoutData(['page_title' => 'Dashboard']);
    }
}
