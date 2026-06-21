<div>
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex gap-2">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cerca per nome o email..."
                        class="form-control form-control-sm"
                        style="width: 300px"
                    >
                    <select wire:model.live="filter" class="form-control form-control-sm" style="width: 200px">
                        <option value="all">Tutti</option>
                        <option value="active">Solo attivi</option>
                        <option value="cert_issues">Certificato scaduto</option>
                    </select>
                </div>
                <a href="{{ route('backoffice.members.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Nuovo tesserato
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>Cognome / Nome</th>
                        <th>Email</th>
                        <th>Abbonamento</th>
                        <th>Scadenza abb.</th>
                        <th>Cert. medico</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($members as $member)
                        @php
                            $sub        = $member->activeSubscription;
                            $certExpiry = $member->medical_cert_expiry;
                            $certExpired = $certExpiry === null || $certExpiry->isPast();
                            $certSoon    = $certExpiry && $certExpiry->isFuture() && $certExpiry->lte(now()->addDays(30));
                        @endphp
                        <tr>
                            <td>{{ $member->last_name }} {{ $member->first_name }}</td>
                            <td>{{ $member->email }}</td>
                            <td>
                                @if ($sub)
                                    <span class="badge badge-success">{{ $sub->plan->name }}</span>
                                @else
                                    <span class="badge badge-danger">Nessuno</span>
                                @endif
                            </td>
                            <td>
                                @if ($sub)
                                    {{ $sub->expires_at->format('d/m/Y') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($certExpiry === null)
                                    <span class="badge badge-danger">Mancante</span>
                                @elseif ($certExpired)
                                    <span class="badge badge-danger">Scaduto</span>
                                @elseif ($certSoon)
                                    <span class="badge badge-warning">{{ $certExpiry->format('d/m/Y') }}</span>
                                @else
                                    <span class="badge badge-success">{{ $certExpiry->format('d/m/Y') }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('backoffice.members.edit', $member) }}" class="btn btn-xs btn-default">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @if ($member->user_id)
                                    <a href="{{ route('backoffice.athletes.profile', ['athleteId' => $member->user_id]) }}"
                                       class="btn btn-xs btn-outline-info ml-1">
                                        <i class="fas fa-dumbbell"></i> Profilo allenamento
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nessun tesserato trovato.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $members->links() }}
        </div>
    </div>
</div>
