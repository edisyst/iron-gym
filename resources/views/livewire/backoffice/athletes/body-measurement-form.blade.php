<div>
    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Nuova misurazione</h3>
                </div>
                <form wire:submit="save">
                    <div class="card-body">
                        <div class="form-group">
                            <label>Data misurazione *</label>
                            <input type="date" class="form-control @error('measuredAt') is-invalid @enderror"
                                   wire:model="measuredAt">
                            @error('measuredAt') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Peso (kg)</label>
                                    <input type="number" step="0.1" class="form-control @error('weightKg') is-invalid @enderror"
                                           wire:model="weightKg" placeholder="es. 80.5">
                                    @error('weightKg') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Body fat %</label>
                                    <input type="number" step="0.1" class="form-control @error('bodyFatPct') is-invalid @enderror"
                                           wire:model="bodyFatPct" placeholder="es. 15.0">
                                    @error('bodyFatPct') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <h6 class="mt-2 mb-2 text-muted">Circonferenze (cm)</h6>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Petto</label>
                                    <input type="number" step="0.1" class="form-control" wire:model="chestCm">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Vita</label>
                                    <input type="number" step="0.1" class="form-control" wire:model="waistCm">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Fianchi</label>
                                    <input type="number" step="0.1" class="form-control" wire:model="hipsCm">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Braccio SX</label>
                                    <input type="number" step="0.1" class="form-control" wire:model="leftArmCm">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Braccio DX</label>
                                    <input type="number" step="0.1" class="form-control" wire:model="rightArmCm">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Coscia SX</label>
                                    <input type="number" step="0.1" class="form-control" wire:model="leftThighCm">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Coscia DX</label>
                                    <input type="number" step="0.1" class="form-control" wire:model="rightThighCm">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Polpaccio SX</label>
                                    <input type="number" step="0.1" class="form-control" wire:model="leftCalfCm">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Polpaccio DX</label>
                                    <input type="number" step="0.1" class="form-control" wire:model="rightCalfCm">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Note</label>
                            <textarea class="form-control" rows="2" wire:model="notes"></textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading wire:target="save" class="spinner-border spinner-border-sm mr-1"></span>
                            Salva misurazione
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ultime 5 misurazioni</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Peso (kg)</th>
                                <th>BF%</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentMeasurements as $m)
                                <tr>
                                    <td>{{ $m->measured_at->format('d/m/Y') }}</td>
                                    <td>{{ $m->weight_kg ?? '—' }}</td>
                                    <td>{{ $m->body_fat_pct !== null ? $m->body_fat_pct.'%' : '—' }}</td>
                                    <td class="text-truncate" style="max-width:140px;">{{ $m->notes ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Nessuna misurazione</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
