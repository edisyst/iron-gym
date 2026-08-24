<div>
    {{-- Filtri --}}
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Filtri</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label class="small">Cerca</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cerca per nome o email..." class="form-control form-control-sm">
                </div>
                <div class="col-md-6">
                    <label class="small">Certificato medico</label>
                    <select wire:model.live="certFilter" class="form-control form-control-sm">
                        <option value="">Tutti</option>
                        <option value="missing">Mancante</option>
                        <option value="expired">Scaduto</option>
                        <option value="expiring_soon">In scadenza (30gg)</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Tesserati</h3>
            <div class="card-tools">
                @role('gestore')
                <a href="{{ route('backoffice.members.export', ['search' => $search, 'certFilter' => $certFilter]) }}"
                   class="btn btn-sm btn-outline-secondary mr-1"
                   title="Esporta CSV">
                    <i class="fas fa-file-csv"></i> Esporta CSV
                </a>
                @endrole
                @can('manage-members')
                <a href="{{ route('backoffice.members.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Nuovo tesserato
                </a>
                @endcan
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
                            <td>
                                {{ $member->last_name }} {{ $member->first_name }}
                                @if ($member->notes)
                                    <i class="fas fa-sticky-note text-warning ml-1"
                                       title="{{ Str::limit($member->notes, 100) }}"
                                       aria-label="Note interne presenti"></i>
                                @endif
                            </td>
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
                                @if (auth()->user()->hasRole('gestore'))
                                    <a href="{{ route('backoffice.members.edit', $member) }}" class="btn btn-sm btn-default" aria-label="Modifica {{ $member->full_name }}">
                                        <i class="fas fa-edit" aria-hidden="true"></i>
                                    </a>
                                @endif
                                @can('access-training-section')
                                    @if ($member->user_id)
                                        <a href="{{ route('backoffice.athletes.profile', ['athleteId' => $member->user_id]) }}"
                                           class="btn btn-sm btn-outline-info ml-1">
                                            <i class="fas fa-dumbbell"></i> Profilo allenamento
                                        </a>
                                    @endif
                                @endcan
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
