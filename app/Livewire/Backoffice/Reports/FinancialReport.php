<?php

namespace App\Livewire\Backoffice\Reports;

use App\Jobs\ExportFinancialReportCsv;
use App\Jobs\ExportMembersListCsv;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class FinancialReport extends Component
{
    public int $year;

    public bool $exportDispatched = false;

    public bool $membersExportDispatched = false;

    public function mount(): void
    {
        $this->year = (int) now()->year;
    }

    public function exportCsv(): void
    {
        ExportFinancialReportCsv::dispatch($this->year, auth()->id());
        $this->exportDispatched = true;
        session()->flash('success', "Export report fiscale {$this->year} avviato. Riceverai una notifica al termine.");
    }

    public function exportMembersList(): void
    {
        ExportMembersListCsv::dispatch(auth()->id());
        $this->membersExportDispatched = true;
        session()->flash('success', 'Export anagrafica tesserati avviato. Riceverai una notifica al termine.');
    }

    public function render(): View
    {
        $rows = DB::table('subscriptions as s')
            ->join('members as m', 'm.id', '=', 's.member_id')
            ->join('subscription_plans as sp', 'sp.id', '=', 's.plan_id')
            ->whereYear('s.started_at', $this->year)
            ->select(
                's.started_at as data',
                DB::raw("CONCAT(m.first_name, ' ', m.last_name) as tesserato"),
                'm.fiscal_code as codice_fiscale',
                'sp.name as piano',
                'sp.price_cents as importo_centesimi',
                'sp.duration_days as durata_giorni',
            )
            ->orderBy('s.started_at')
            ->get();

        $totalCents = $rows->sum('importo_centesimi');

        return view('livewire.backoffice.reports.financial-report', compact('rows', 'totalCents'))
            ->layout('layouts.backoffice')
            ->layoutData(['page_title' => "Report finanziario {$this->year}"]);
    }
}
