<div>
    <div class="row">
        {{-- Pannello check-in --}}
        <div class="col-md-5">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-door-open mr-1"></i> Registra accesso</h3>
                </div>
                <div class="card-body">
                    @if ($successMessage)
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" wire:click="$set('successMessage', '')">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <i class="fas fa-check-circle mr-1"></i> {{ $successMessage }}
                        </div>
                    @endif

                    @if ($errorMessage)
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" wire:click="$set('errorMessage', '')">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <i class="fas fa-exclamation-circle mr-1"></i> {{ $errorMessage }}
                        </div>
                    @endif

                    <div class="form-group">
                        <label>Cerca tesserato</label>
                        <input
                            type="text"
                            wire:model.live.debounce.200ms="search"
                            placeholder="Nome, cognome o email..."
                            class="form-control"
                            autocomplete="off"
                        >
                    </div>

                    {{-- Risultati ricerca --}}
                    @if ($searchResults->isNotEmpty())
                        <div class="list-group mb-3">
                            @foreach ($searchResults as $member)
                                @php
                                    $sub = $member->activeSubscription;
                                    $certOk = $member->has_medical_cert_valid;
                                @endphp
                                <button
                                    type="button"
                                    wire:click="selectMember({{ $member->id }})"
                                    class="list-group-item list-group-item-action"
                                >
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>{{ $member->last_name }} {{ $member->first_name }}</strong>
                                            <small class="text-muted d-block">{{ $member->email }}</small>
                                        </div>
                                        <div class="text-right">
                                            @if ($sub)
                                                <span class="badge badge-success">{{ $sub->plan->name }}</span>
                                            @else
                                                <span class="badge badge-danger">No abb.</span>
                                            @endif
                                            @if (! $certOk)
                                                <span class="badge badge-warning ml-1">Cert. scaduto</span>
                                            @endif
                                        </div>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @elseif (strlen($search) >= 2 && ! $selectedMemberId)
                        <p class="text-muted small">Nessun tesserato trovato.</p>
                    @endif

                    <button
                        type="button"
                        wire:click="registerAccess"
                        class="btn btn-primary btn-block"
                        @disabled(! $selectedMemberId)
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove><i class="fas fa-sign-in-alt mr-1"></i> Registra accesso</span>
                        <span wire:loading>Registrazione...</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Accessi odierni --}}
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list mr-1"></i>
                        Accessi oggi
                        <span class="badge badge-primary ml-2">{{ $todayLogs->count() }}</span>
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('backoffice.access-logs.index') }}" class="btn btn-sm btn-outline-secondary">
                            Registro completo
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if ($todayLogs->isEmpty())
                        <p class="text-muted p-3 mb-0">Nessun accesso registrato oggi.</p>
                    @else
                        <table class="table table-hover table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Tesserato</th>
                                    <th>Piano</th>
                                    <th>Ora</th>
                                    <th>Registrato da</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($todayLogs as $log)
                                    <tr>
                                        <td>
                                            @if ($log->member)
                                                {{ $log->member->last_name }} {{ $log->member->first_name }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if ($log->subscription)
                                                <span class="badge badge-success">{{ $log->subscription->plan->name }}</span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $log->checked_in_at->format('H:i') }}</td>
                                        <td>
                                            @if ($log->checkedInBy)
                                                <small>{{ $log->checkedInBy->name }}</small>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
