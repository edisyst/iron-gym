<div>
    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    {{-- Modale conferma --}}
    @if ($confirmActive)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="modal-flag-title" style="background:rgba(0,0,0,.5)">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-flag-title">Conferma</h5>
                    </div>
                    <div class="modal-body">
                        <p>
                            {{ $pendingState ? 'Attivare' : 'Disattivare' }}
                            <strong>{{ $managedFlags[$pendingFlag]['label'] ?? $pendingFlag }}</strong> per tutti?
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button wire:click="cancelToggle" class="btn btn-secondary btn-sm">Annulla</button>
                        <button wire:click="confirmToggle" class="btn btn-{{ $pendingState ? 'success' : 'danger' }} btn-sm">
                            Conferma
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @php
        $groups = [];
        foreach ($managedFlags as $flagKey => $meta) {
            $groups[$meta['group']][$flagKey] = $meta;
        }
    @endphp

    @foreach ($groups as $groupName => $flagsInGroup)
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ $groupName }}</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Funzione</th>
                            <th>Descrizione</th>
                            <th>Visibile a</th>
                            <th class="text-center" style="width:120px">Stato</th>
                            <th class="text-center" style="width:160px">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($flagsInGroup as $flag => $meta)
                            <tr>
                                <td class="align-middle">
                                    <strong>{{ $meta['label'] }}</strong><br>
                                    <small class="text-muted"><code>{{ $flag }}</code></small>
                                </td>
                                <td class="align-middle">{{ $meta['description'] }}</td>
                                <td class="align-middle">
                                    <small class="text-muted">{{ $meta['platea'] }}</small>
                                </td>
                                <td class="text-center align-middle">
                                    @if ($statuses[$flag])
                                        <span class="badge badge-success">Attivo</span>
                                    @else
                                        <span class="badge badge-secondary">Inattivo</span>
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    @if ($statuses[$flag])
                                        <button wire:click="requestToggle('{{ $flag }}', false)"
                                                class="btn btn-danger btn-sm">
                                            Disattiva
                                        </button>
                                    @else
                                        <button wire:click="requestToggle('{{ $flag }}', true)"
                                                class="btn btn-success btn-sm">
                                            Attiva
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
