<div>
    {{-- Filtri --}}
    <div class="card card-outline card-primary mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center g-2">
                <div class="col-auto">
                    <label class="mb-0 mr-1 small">Tipo</label>
                    <select wire:model.live="filterType" class="form-control form-control-sm d-inline-block w-auto">
                        <option value="">Tutti</option>
                        <option value="bug">Bug</option>
                        <option value="suggestion">Suggerimento</option>
                        <option value="confused">Confuso su…</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="mb-0 mr-1 small">Dal</label>
                    <input type="date" wire:model.live="filterFrom" class="form-control form-control-sm d-inline-block w-auto">
                </div>
                <div class="col-auto">
                    <label class="mb-0 mr-1 small">Al</label>
                    <input type="date" wire:model.live="filterTo" class="form-control form-control-sm d-inline-block w-auto">
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-sm table-bordered mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width:130px">Data</th>
                        <th style="width:100px">Tipo</th>
                        <th>Utente</th>
                        <th>Pagina</th>
                        <th>Testo</th>
                        <th style="width:220px">Note interne</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($feedbacks as $fb)
                        <tr>
                            <td class="align-top small text-muted">{{ $fb->created_at->format('d/m/Y H:i') }}</td>
                            <td class="align-top">
                                @php $badge = match($fb->type) {
                                    'bug'        => 'danger',
                                    'suggestion' => 'primary',
                                    'confused'   => 'warning',
                                    default      => 'secondary',
                                }; @endphp
                                <span class="badge badge-{{ $badge }}">{{ $fb->type }}</span>
                            </td>
                            <td class="align-top small">{{ $fb->user?->email ?? '—' }}</td>
                            <td class="align-top small text-break" style="max-width:150px;">{{ $fb->page_url }}</td>
                            <td class="align-top small" style="max-width:300px; white-space:pre-wrap;">{{ $fb->body }}</td>
                            <td class="align-top">
                                <textarea
                                    wire:change="saveNotes({{ $fb->id }}, $event.target.value)"
                                    rows="2"
                                    class="form-control form-control-sm"
                                    placeholder="Note interne…"
                                >{{ $fb->internal_notes }}</textarea>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Nessun feedback.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($feedbacks->hasPages())
            <div class="card-footer">
                {{ $feedbacks->links() }}
            </div>
        @endif
    </div>
</div>
