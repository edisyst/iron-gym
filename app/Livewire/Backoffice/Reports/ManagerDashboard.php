<?php

namespace App\Livewire\Backoffice\Reports;

use App\Services\KpiService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Laravel\Pennant\Feature;
use Livewire\Component;

class ManagerDashboard extends Component
{
    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        abort_unless(Feature::active('financial_reports'), 403);
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->endOfMonth()->toDateString();
    }

    private function from(): Carbon
    {
        return Carbon::parse($this->dateFrom)->startOfDay();
    }

    private function to(): Carbon
    {
        return Carbon::parse($this->dateTo)->endOfDay();
    }

    /** @return array<string, int> */
    public function getRevenueChartData(): array
    {
        $from = now()->subMonths(11)->startOfMonth();
        $to = now()->endOfMonth();

        return app(KpiService::class)->revenueByPeriod($from, $to);
    }

    /** @return list<array{plan: string, revenue_cents: int, count: int}> */
    public function getPlanRevenueData(): array
    {
        return app(KpiService::class)->revenueByPlan($this->from(), $this->to());
    }

    /** @return list<array{trainer: string, slots_available: int, slots_booked: int, occupancy_pct: float}> */
    public function getTrainerOccupancyData(): array
    {
        return app(KpiService::class)->trainerOccupancy($this->from(), $this->to());
    }

    public function render(): View
    {
        $kpi = app(KpiService::class);
        $from = $this->from();
        $to = $this->to();

        $revenueCents = array_sum($kpi->revenueByPeriod($from, $to));
        $revenueEuro = number_format($revenueCents / 100, 2, ',', '.');

        $revenueChart = $this->getRevenueChartData();
        $planRevenue = $this->getPlanRevenueData();
        $trainerOccupancy = $this->getTrainerOccupancyData();
        $trainerRevenue = $kpi->revenueByTrainer($from, $to);

        // Abbonati a rischio churn: scaduti da 0-30 giorni senza rinnovo
        $atRiskMembers = Cache::remember('at_risk_members:'.now()->toDateString(), 300, function () {
            return DB::table('subscriptions as s')
                ->join('members as m', 'm.id', '=', 's.member_id')
                ->leftJoin('subscriptions as s2', function ($j) {
                    $j->on('s2.member_id', '=', 's.member_id')
                        ->on('s2.id', '!=', 's.id')
                        ->whereColumn('s2.started_at', '>', 's.expires_at');
                })
                ->leftJoin('access_logs as al', 'al.member_id', '=', 'm.id')
                ->whereNull('s2.id')
                ->whereBetween('s.expires_at', [
                    now()->subDays(30)->toDateString(),
                    now()->toDateString(),
                ])
                ->select(
                    'm.id as member_id',
                    DB::raw(DB::connection()->getDriverName() === 'sqlite'
                        ? "(m.first_name || ' ' || m.last_name) as nome"
                        : "CONCAT(m.first_name, ' ', m.last_name) as nome"),
                    's.expires_at',
                    DB::raw('MAX(al.checked_in_at) as last_access'),
                )
                ->groupBy('m.id', 'm.first_name', 'm.last_name', 's.id', 's.expires_at')
                ->orderBy('s.expires_at')
                ->get();
        });

        $ptSessionsPerTrainer = DB::table('pt_bookings')
            ->join('users', 'users.id', '=', 'pt_bookings.trainer_id')
            ->whereBetween('pt_bookings.booked_date', [$from->toDateString(), $to->toDateString()])
            ->where('pt_bookings.status', 'completed')
            ->groupBy('pt_bookings.trainer_id', 'users.name')
            ->select('users.name as trainer', DB::raw('COUNT(*) as sessions_count'))
            ->orderByDesc('sessions_count')
            ->get();

        return view('livewire.backoffice.reports.manager-dashboard', [
            'revenueEuro' => $revenueEuro,
            'newMembers' => $kpi->newMembersCount($from, $to),
            'retentionRate' => $kpi->retentionRate($from, $to),
            'churnRate' => $kpi->churnRate($from, $to),
            'revenueChart' => $revenueChart,
            'planRevenue' => $planRevenue,
            'trainerOccupancy' => $trainerOccupancy,
            'trainerRevenue' => $trainerRevenue,
            'atRiskMembers' => $atRiskMembers,
            'ptSessionsPerTrainer' => $ptSessionsPerTrainer,
        ])->layout('layouts.backoffice')->layoutData(['page_title' => 'Dashboard gestore']);
    }
}
