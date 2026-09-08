<div>
    {{-- Modale registra accesso --}}
    @if ($showModal)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="modal-accesso-title">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-accesso-title">Registra accesso</h5>
                        <button type="button" class="close" wire:click="closeModal" aria-label="Chiudi"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        @if ($checkinError)
                            <div class="alert alert-danger">{{ $checkinError }}</div>
                        @endif
                        <div class="form-group">
                            <label>Cerca tesserato</label>
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="checkinSearch"
                                class="form-control"
                                placeholder="Almeno 2 caratteri..."
                                autofocus
                            >
                        </div>
                        @if ($modalMembers->isNotEmpty())
                            <div class="list-group mt-2">
                                @foreach ($modalMembers as $m)
                                    <button
                                        type="button"
                                        class="list-group-item list-group-item-action {{ $checkinMemberId === $m->id ? 'active' : '' }}"
                                        wire:click="selectMember({{ $m->id }})"
                                    >
                                        {{ $m->last_name }} {{ $m->first_name }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">Annulla</button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            wire:click="registerAccess"
                            wire:loading.attr="disabled"
                            @if (! $checkinMemberId) disabled @endif
                        >
                            <span wire:loading wire:target="registerAccess" class="spinner-border spinner-border-sm mr-1"></span>
                            Conferma accesso
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <x-bo.filters>
        <div class="row">
            <div class="col-md-3">
                <label class="small">Data</label>
                <input type="date" wire:model.live="dateFilter" class="form-control form-control-sm">
            </div>
            <div class="col-md-5">
                <label class="small">Cerca tesserato</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Nome o cognome..." class="form-control form-control-sm">
            </div>
        </div>
    </x-bo.filters>

    <x-bo.card bodyClass="p-0">
        <x-slot name="actions">
            <button class="btn btn-primary btn-sm" wire:click="openModal" wire:loading.attr="disabled">
                <span wire:loading wire:target="openModal" class="spinner-border spinner-border-sm mr-1"></span>
                <i class="fas fa-sign-in-alt" wire:loading.remove wire:target="openModal"></i>
                Registra accesso
            </button>
        </x-slot>

        <div class="table-responsive">
        <table class="table table-sm table-striped table-hover mb-0">
            <thead>
                <tr>
                    <th>Data / Ora</th>
                    <th>Tesserato</th>
                    <th>Piano</th>
                    <th>Receptionist</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td>{{ $log->checked_in_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $log->member->last_name }} {{ $log->member->first_name }}</td>
                        <td>{{ $log->subscription?->plan->name ?? '—' }}</td>
                        <td>{{ $log->checkedInBy?->name ?? 'Sistema' }}</td>
                    </tr>
                @empty
                    <x-bo.empty :colspan="4">Nessun accesso trovato.</x-bo.empty>
                @endforelse
            </tbody>
        </table>
        </div>

        <x-slot name="footer">
            <x-bo.pagination :paginator="$logs" />
        </x-slot>
    </x-bo.card>
</div>
