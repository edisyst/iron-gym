<div>
    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Volume landmarks — {{ $athlete->name }}</h3>
            <div>
                <button wire:click="save" class="btn btn-primary btn-sm">
                    <i class="fas fa-save mr-1"></i> Salva
                </button>
                <button wire:click="resetToDefaults"
                        wire:confirm="Ripristinare i valori di default? I valori personalizzati verranno eliminati."
                        class="btn btn-secondary btn-sm ml-1">
                    <i class="fas fa-undo mr-1"></i> Ripristina default
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            @foreach ($grouped as $group => $muscles)
                <table class="table table-sm table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th colspan="5" class="text-uppercase small font-weight-bold text-muted py-1 px-3 bg-light">
                                {{ ucfirst($group) }}
                            </th>
                        </tr>
                        <tr>
                            <th style="width:30%">Muscolo</th>
                            <th class="text-center">MEV</th>
                            <th class="text-center">MAV min</th>
                            <th class="text-center">MAV max</th>
                            <th class="text-center">MRV</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($muscles as $slug => $lm)
                            <tr>
                                <td class="align-middle">{{ $lm['name_it'] }}</td>
                                @foreach (['mev', 'mav_min', 'mav_max', 'mrv'] as $field)
                                    <td class="text-center p-1">
                                        <input type="number"
                                               wire:model.lazy="landmarks.{{ $slug }}.{{ $field }}"
                                               class="form-control form-control-sm text-center @error("landmarks.{$slug}.{$field}") is-invalid @enderror"
                                               min="0" max="60" style="width:60px;margin:auto">
                                        @error("landmarks.{$slug}.{$field}")
                                            <span class="invalid-feedback d-block" style="font-size:10px">{{ $message }}</span>
                                        @enderror
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endforeach
        </div>
    </div>
</div>
