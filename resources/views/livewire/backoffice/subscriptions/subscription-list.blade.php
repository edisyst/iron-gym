<div>
    {{-- Filtri --}}
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Filtri</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <label class="small">Stato</label>
                    <select wire:model.live="filter" class="form-control form-control-sm">
                        <option value="all">Tutti</option>
                        <option value="active">Attivi</option>
                        <option value="expiring">In scadenza (30gg)</option>
                        <option value="expired">Scaduti</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Abbonamenti</h3>
            <div class="card-tools">
                @can('manage-subscriptions')
                <a href="{{ route('backoffice.subscriptions.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Nuovo abbonamento
                </a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Tesserato</th>
                        <th>Piano</th>
                        <th>Inizio</th>
                        <th>Scadenza</th>
                        <th>Accessi</th>
                        <th>Stato</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($subscriptions as $sub)
                        <tr>
                            <td>{{ $sub->member->last_name }} {{ $sub->member->first_name }}</td>
                            <td>{{ $sub->plan->name }}</td>
                            <td>{{ $sub->started_at->format('d/m/Y') }}</td>
                            <td>{{ $sub->expires_at->format('d/m/Y') }}</td>
                            <td>
                                {{ $sub->accesses_used }}
                                @if ($sub->accesses_remaining !== null)
                                    / {{ $sub->accesses_used + $sub->accesses_remaining }}
                                @else
                                    / ∞
                                @endif
                            </td>
                            <td>
                                @php
                                    [$badge, $label] = match($sub->status) {
                                        'active'    => ['success',   'Attivo'],
                                        'expired'   => ['danger',    'Scaduto'],
                                        'suspended' => ['warning',   'Sospeso'],
                                        'cancelled' => ['secondary', 'Cancellato'],
                                        default     => ['secondary', ucfirst($sub->status)],
                                    };
                                @endphp
                                <span class="badge badge-{{ $badge }}">{{ $label }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nessun abbonamento trovato.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $subscriptions->links() }}
        </div>
    </div>
</div>
