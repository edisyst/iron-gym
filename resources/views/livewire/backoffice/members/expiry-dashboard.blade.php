<div>
    {{-- Filtri --}}
    <div class="card card-outline card-warning">
        <div class="card-header">
            <h3 class="card-title">Filtri</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <label class="small">Cerca tesserato</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Nome, cognome o email..." class="form-control form-control-sm">
                </div>
                <div class="col-md-4">
                    <label class="small">Certificati in scadenza entro</label>
                    <select wire:model.live="certDays" class="form-control form-control-sm">
                        <option value="7">7 giorni</option>
                        <option value="14">14 giorni</option>
                        <option value="30" selected>30 giorni</option>
                        <option value="60">60 giorni</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small">Abbonamenti in scadenza entro</label>
                    <select wire:model.live="subDays" class="form-control form-control-sm">
                        <option value="3">3 giorni</option>
                        <option value="7" selected>7 giorni</option>
                        <option value="14">14 giorni</option>
                        <option value="30">30 giorni</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Certificati medici in scadenza --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-file-medical mr-1"></i>
                Certificati medici in scadenza
                <span class="badge badge-{{ $expiringCerts->count() > 0 ? 'warning' : 'success' }} ml-2">
                    {{ $expiringCerts->count() }}
                </span>
            </h3>
        </div>
        <div class="card-body p-0">
            @if ($expiringCerts->isEmpty())
                <p class="text-muted p-3 mb-0">Nessun certificato in scadenza nel periodo selezionato.</p>
            @else
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Tesserato</th>
                            <th>Email</th>
                            <th>Scadenza cert.</th>
                            <th>Giorni rimanenti</th>
                            <th>Abbonamento</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($expiringCerts as $member)
                            @php
                                $daysLeft = (int) now()->diffInDays($member->medical_cert_expiry, false);
                                $badgeClass = $daysLeft < 0 ? 'danger' : ($daysLeft <= 7 ? 'danger' : 'warning');
                            @endphp
                            <tr>
                                <td>{{ $member->last_name }} {{ $member->first_name }}</td>
                                <td>{{ $member->email }}</td>
                                <td>
                                    <span class="badge badge-{{ $badgeClass }}">
                                        {{ $member->medical_cert_expiry->format('d/m/Y') }}
                                    </span>
                                </td>
                                <td>
                                    @if ($daysLeft < 0)
                                        <span class="text-danger">Scaduto ({{ abs($daysLeft) }}gg fa)</span>
                                    @elseif ($daysLeft === 0)
                                        <span class="text-danger font-weight-bold">Oggi</span>
                                    @else
                                        <span class="text-{{ $daysLeft <= 7 ? 'danger' : 'warning' }}">{{ $daysLeft }} giorni</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($member->activeSubscription)
                                        <span class="badge badge-success">{{ $member->activeSubscription->plan->name }}</span>
                                    @else
                                        <span class="badge badge-secondary">Nessuno</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('backoffice.members.edit', $member) }}" class="btn btn-sm btn-outline-primary" aria-label="Modifica tesserato">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- Abbonamenti in scadenza --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-id-card mr-1"></i>
                Abbonamenti in scadenza
                <span class="badge badge-{{ $expiringSubs->count() > 0 ? 'warning' : 'success' }} ml-2">
                    {{ $expiringSubs->count() }}
                </span>
            </h3>
        </div>
        <div class="card-body p-0">
            @if ($expiringSubs->isEmpty())
                <p class="text-muted p-3 mb-0">Nessun abbonamento in scadenza nel periodo selezionato.</p>
            @else
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Tesserato</th>
                            <th>Email</th>
                            <th>Piano</th>
                            <th>Scadenza abb.</th>
                            <th>Giorni rimanenti</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($expiringSubs as $sub)
                            @php
                                $daysLeft = (int) now()->diffInDays($sub->expires_at, false);
                                $badgeClass = $daysLeft <= 3 ? 'danger' : 'warning';
                            @endphp
                            <tr>
                                <td>{{ $sub->member->last_name }} {{ $sub->member->first_name }}</td>
                                <td>{{ $sub->member->email }}</td>
                                <td>{{ $sub->plan->name }}</td>
                                <td>
                                    <span class="badge badge-{{ $badgeClass }}">
                                        {{ $sub->expires_at->format('d/m/Y') }}
                                    </span>
                                </td>
                                <td>
                                    @if ($daysLeft === 0)
                                        <span class="text-danger font-weight-bold">Oggi</span>
                                    @else
                                        <span class="text-{{ $badgeClass }}">{{ $daysLeft }} giorni</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('backoffice.subscriptions.create') }}" class="btn btn-sm btn-outline-success" aria-label="Nuovo abbonamento">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
