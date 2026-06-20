<?php

namespace App\Console\Commands;

use App\Services\KpiService;
use Illuminate\Console\Command;

class KpiSummaryCommand extends Command
{
    protected $signature = 'reports:kpi-summary';

    protected $description = 'Stampa il riepilogo KPI del mese corrente in tabella ASCII.';

    public function handle(KpiService $kpi): int
    {
        $from = now()->startOfMonth();
        $to = now()->endOfMonth();

        $revenueCents = array_sum($kpi->revenueByPeriod($from, $to));

        $this->info("KPI — {$from->format('F Y')}");
        $this->newLine();

        $this->table(['Metrica', 'Valore'], [
            ['Fatturato periodo', '€ '.number_format($revenueCents / 100, 2, ',', '.')],
            ['Nuovi iscritti', $kpi->newMembersCount($from, $to)],
            ['Membri attivi', $kpi->activeMembersCount($from, $to)],
            ['Retention rate', $kpi->retentionRate($from, $to).'%'],
            ['Churn rate', $kpi->churnRate($from, $to).'%'],
            ['Media sessioni/membro', $kpi->avgSessionsPerMember($from, $to)],
        ]);

        $this->newLine();
        $this->info('Fatturato per piano:');
        $planData = $kpi->revenueByPlan($from, $to);
        if (empty($planData)) {
            $this->line('  Nessun dato.');
        } else {
            $this->table(
                ['Piano', 'Importo (€)', 'N. abbonamenti'],
                array_map(fn ($r) => [
                    $r['plan'],
                    '€ '.number_format($r['revenue_cents'] / 100, 2, ',', '.'),
                    $r['count'],
                ], $planData),
            );
        }

        $this->newLine();
        $this->info('Occupancy trainer:');
        $occupancy = $kpi->trainerOccupancy($from, $to);
        if (empty($occupancy)) {
            $this->line('  Nessun dato.');
        } else {
            $this->table(
                ['Trainer', 'Slot disponibili', 'Slot prenotati', 'Occupancy %'],
                array_map(fn ($r) => [
                    $r['trainer'],
                    $r['slots_available'],
                    $r['slots_booked'],
                    $r['occupancy_pct'].'%',
                ], $occupancy),
            );
        }

        return self::SUCCESS;
    }
}
