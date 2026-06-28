<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <select wire:model.live="filter" class="form-control form-control-sm filter-w-md">
                <option value="all">Tutti</option>
                <option value="active">Attivi</option>
                <option value="expiring">In scadenza (30gg)</option>
                <option value="expired">Scaduti</option>
            </select>
            <a href="{{ route('backoffice.subscriptions.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Nuovo abbonamento
            </a>
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
                                    $badge = match($sub->status) {
                                        'active'    => 'success',
                                        'expired'   => 'danger',
                                        'suspended' => 'warning',
                                        'cancelled' => 'secondary',
                                        default     => 'secondary',
                                    };
                                @endphp
                                <span class="badge badge-{{ $badge }}">{{ ucfirst($sub->status) }}</span>
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
