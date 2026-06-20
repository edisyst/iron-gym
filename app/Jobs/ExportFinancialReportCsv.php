<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\ReportReadyNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use SplTempFileObject;

class ExportFinancialReportCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $year,
        public readonly int $requestedByUserId,
    ) {}

    public function handle(): void
    {
        $rows = DB::table('subscriptions as s')
            ->join('members as m', 'm.id', '=', 's.member_id')
            ->join('subscription_plans as sp', 'sp.id', '=', 's.plan_id')
            ->whereYear('s.started_at', $this->year)
            ->select(
                's.started_at as data',
                'm.last_name as cognome',
                'm.first_name as nome',
                'm.fiscal_code as codice_fiscale',
                'sp.name as piano',
                'sp.price_cents as importo_centesimi',
                'sp.duration_days as durata_giorni',
            )
            ->orderBy('s.started_at')
            ->get();

        $csv = Writer::createFromFileObject(new SplTempFileObject);
        $csv->setDelimiter(';');

        $csv->insertOne(['Data', 'Cognome', 'Nome', 'Codice Fiscale', 'Piano', 'Importo (€)', 'Durata (giorni)']);

        $total = 0;
        foreach ($rows as $row) {
            $importoEuro = number_format($row->importo_centesimi / 100, 2, ',', '.');
            $csv->insertOne([
                $row->data,
                $row->cognome,
                $row->nome,
                $row->codice_fiscale ?? '',
                $row->piano,
                $importoEuro,
                $row->durata_giorni,
            ]);
            $total += (int) $row->importo_centesimi;
        }

        $csv->insertOne(['', '', '', '', 'TOTALE', number_format($total / 100, 2, ',', '.'), '']);

        $timestamp = now()->format('YmdHis');
        $filename = "reports/report_fiscale_{$this->year}_{$timestamp}.csv";

        Storage::disk('local')->put("private/{$filename}", $csv->toString());

        $downloadRoute = route('backoffice.reports.download', ['file' => "report_fiscale_{$this->year}_{$timestamp}.csv"]);

        $user = User::find($this->requestedByUserId);
        $user?->notify(new ReportReadyNotification(
            reportName: "Report fiscale {$this->year}",
            downloadRoute: $downloadRoute,
        ));
    }
}
