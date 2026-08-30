<?php

namespace App\Livewire\Backoffice;

use App\Models\AccessLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
            $cutoff30 = now()->addDays(30)->toDateString();
            $cutoff7 = now()->addDays(7)->toDateString();
            $today = today()->toDateString();

            $memberStats = DB::table('members')
                ->where('is_active', true)
                ->selectRaw(
                    'COUNT(*) as active_count,
                    SUM(CASE WHEN medical_cert_expiry IS NULL OR medical_cert_expiry <= ? THEN 1 ELSE 0 END) as cert_issues,
                    SUM(CASE WHEN medical_cert_expiry IS NOT NULL AND medical_cert_expiry >= ? AND medical_cert_expiry <= ? THEN 1 ELSE 0 END) as cert_expiring30',
                    [$cutoff30, $today, $cutoff30]
                )
                ->first();

            $subStats = DB::table('subscriptions')
                ->where('status', 'active')
                ->where('expires_at', '>=', $today)
                ->where('expires_at', '<=', $cutoff30)
                ->selectRaw(
                    'COUNT(*) as expiring30, SUM(CASE WHEN expires_at <= ? THEN 1 ELSE 0 END) as expiring7',
                    [$cutoff7]
                )
                ->first();

            return [
                'activeMembersCount' => (int) ($memberStats->active_count ?? 0),
                'medicalCertIssuesCount' => (int) ($memberStats->cert_issues ?? 0),
                'certExpiring30Count' => (int) ($memberStats->cert_expiring30 ?? 0),
                'expiringSoonCount' => (int) ($subStats->expiring30 ?? 0),
                'subExpiring7Count' => (int) ($subStats->expiring7 ?? 0),
            ];
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
