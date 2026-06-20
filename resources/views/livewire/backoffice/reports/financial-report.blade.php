<div>
    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            {{ session('success') }}
        </div>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-auto">
                    <label class="mb-0 mr-1">Anno fiscale</label>
                    <select class="form-control form-control-sm d-inline-block w-auto" wire:model.live="year">
                        @for ($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-auto ml-auto">
                    <button class="btn btn-sm btn-success" wire:click="exportCsv" wire:loading.attr="disabled">
                        <span wire:loading wire:target="exportCsv"><i class="fas fa-spinner fa-spin mr-1"></i></span>
                        Esporta CSV report fiscale
                    </button>
                    <button class="btn btn-sm btn-outline-secondary ml-2" wire:click="exportMembersList" wire:loading.attr="disabled">
                        <span wire:loading wire:target="exportMembersList"><i class="fas fa-spinner fa-spin mr-1"></i></span>
                        Esporta anagrafica tesserati
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Abbonamenti venduti — {{ $year }}</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-striped mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tesserato</th>
                        <th>Codice Fiscale</th>
                        <th>Piano</th>
                        <th class="text-right">Importo</th>
                        <th>Durata (gg)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($row->data)->format('d/m/Y') }}</td>
                            <td>{{ $row->tesserato }}</td>
                            <td>{{ $row->codice_fiscale ?? '—' }}</td>
                            <td>{{ $row->piano }}</td>
                            <td class="text-right">€ {{ number_format($row->importo_centesimi / 100, 2, ',', '.') }}</td>
                            <td>{{ $row->durata_giorni }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">Nessun abbonamento nel {{ $year }}.</td></tr>
                    @endforelse
                </tbody>
                @if ($rows->count() > 0)
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-right"><strong>Totale</strong></td>
                            <td class="text-right"><strong>€ {{ number_format($totalCents / 100, 2, ',', '.') }}</strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
