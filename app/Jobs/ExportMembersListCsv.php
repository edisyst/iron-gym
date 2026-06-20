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

class ExportMembersListCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $requestedByUserId,
    ) {}

    public function handle(): void
    {
        $rows = DB::table('members as m')
            ->leftJoin('subscriptions as s', function ($j) {
                $j->on('s.member_id', '=', 'm.id')
                    ->where('s.status', 'active')
                    ->where('s.expires_at', '>=', now()->toDateString());
            })
            ->leftJoin('subscription_plans as sp', 'sp.id', '=', 's.plan_id')
            ->select(
                'm.last_name as cognome',
                'm.first_name as nome',
                'm.email',
                'm.phone as telefono',
                'm.fiscal_code as codice_fiscale',
                'm.date_of_birth as data_nascita',
                DB::raw("CASE WHEN s.id IS NOT NULL THEN 'Attivo' ELSE 'Non attivo' END as stato_abbonamento"),
            )
            ->orderBy('m.last_name')
            ->orderBy('m.first_name')
            ->get();

        $csv = Writer::createFromFileObject(new SplTempFileObject);
        $csv->setDelimiter(';');

        $csv->insertOne(['Cognome', 'Nome', 'Email', 'Telefono', 'Codice Fiscale', 'Data Nascita', 'Stato Abbonamento']);

        foreach ($rows as $row) {
            $csv->insertOne([
                $row->cognome,
                $row->nome,
                $row->email ?? '',
                $row->telefono ?? '',
                $row->codice_fiscale ?? '',
                $row->data_nascita ?? '',
                $row->stato_abbonamento,
            ]);
        }

        $timestamp = now()->format('YmdHis');
        $filename = "reports/anagrafica_tesserati_{$timestamp}.csv";

        Storage::disk('local')->put("private/{$filename}", $csv->toString());

        $downloadRoute = route('backoffice.reports.download', ['file' => "anagrafica_tesserati_{$timestamp}.csv"]);

        $user = User::find($this->requestedByUserId);
        $user?->notify(new ReportReadyNotification(
            reportName: 'Anagrafica tesserati',
            downloadRoute: $downloadRoute,
        ));
    }
}
