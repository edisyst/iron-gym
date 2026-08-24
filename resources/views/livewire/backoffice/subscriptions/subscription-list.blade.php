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
                        <option value="suspended">Sospesi</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Abbonamenti</h3>
            <div class="card-tools">
                @role('gestore')
                <a href="{{ route('backoffice.subscriptions.export', ['filter' => $filter]) }}"
                   class="btn btn-sm btn-outline-secondary mr-1"
                   title="Esporta CSV">
                    <i class="fas fa-file-csv"></i> Esporta CSV
                </a>
                @endrole
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
                        <th></th>
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
                            <td class="text-right" style="white-space:nowrap;">
                                @can('manage-subscriptions')
                                <a
                                    href="{{ route('backoffice.subscriptions.create', ['member_id' => $sub->member_id, 'plan_id' => $sub->plan_id]) }}"
                                    class="btn btn-sm btn-outline-success mr-1"
                                    aria-label="Rinnova abbonamento"
                                    title="Rinnova"
                                >
                                    <i class="fas fa-redo"></i>
                                </a>
                                @endcan
                                @can('manage-subscriptions')
                                @if ($sub->status === 'active')
                                    <button
                                        type="button"
                                        wire:click="suspend({{ $sub->id }})"
                                        wire:confirm="Sospendere questo abbonamento?"
                                        class="btn btn-sm btn-outline-warning"
                                        aria-label="Sospendi abbonamento"
                                        title="Sospendi"
                                    >
                                        <i class="fas fa-pause"></i>
                                    </button>
                                @elseif ($sub->status === 'suspended')
                                    <button
                                        type="button"
                                        wire:click="reactivate({{ $sub->id }})"
                                        wire:confirm="Riattivare questo abbonamento?"
                                        class="btn btn-sm btn-outline-primary"
                                        aria-label="Riattiva abbonamento"
                                        title="Riattiva"
                                    >
                                        <i class="fas fa-play"></i>
                                    </button>
                                @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Nessun abbonamento trovato.</td>
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
