<div>
    <x-bo.filters>
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
        <div class="form-group mb-0 mt-2">
            <button wire:click="resetFilters" class="btn btn-default btn-sm">
                <i class="fas fa-times mr-1"></i> Azzera filtri
            </button>
        </div>
    </x-bo.filters>

    <x-bo.card bodyClass="p-0">
        <x-slot name="actions">
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
        </x-slot>

        <div class="table-responsive">
        <table class="table table-sm table-striped table-hover mb-0">
            <thead>
                <tr>
                    <th>Cognome / Nome</th>
                    <th>Email</th>
                    <th>Abbonamento</th>
                    <th>Scadenza abb.</th>
                    <th>Cert. medico</th>
                    <th class="text-right table-actions">Azioni</th>
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
                    <x-bo.empty :colspan="6">Nessun tesserato trovato.</x-bo.empty>
                @endforelse
            </tbody>
        </table>
        </div>

        <x-slot name="footer">
            <x-bo.pagination :paginator="$members" />
        </x-slot>
    </x-bo.card>
</div>
