<?php

namespace App\Livewire\Backoffice\Reports;

use App\Services\KpiService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class ManagerDashboard extends Component
{
    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
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
        $atRiskMembers = DB::table('subscriptions as s')
            ->join('members as m', 'm.id', '=', 's.member_id')
            ->leftJoin('subscriptions as s2', function ($j) {
                $j->on('s2.member_id', '=', 's.member_id')
                    ->on('s2.id', '!=', 's.id')
                    ->whereColumn('s2.started_at', '>', 's.expires_at');
            })
            ->whereNull('s2.id')
            ->whereBetween('s.expires_at', [
                now()->subDays(30)->toDateString(),
                now()->toDateString(),
            ])
            ->select(
                'm.id as member_id',
                DB::raw("CONCAT(m.first_name, ' ', m.last_name) as nome"),
                's.expires_at',
                DB::raw('(SELECT MAX(checked_in_at) FROM access_logs WHERE member_id = m.id) as last_access'),
            )
            ->orderBy('s.expires_at')
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
        ])->layout('layouts.backoffice')->layoutData(['page_title' => 'Dashboard gestore']);
    }
}
