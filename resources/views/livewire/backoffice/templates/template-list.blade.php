<div>
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex flex-wrap gap-2">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cerca template..."
                        class="form-control form-control-sm"
                        style="width: 220px"
                    >
                    <select wire:model.live="goal" class="form-control form-control-sm" style="width: 160px">
                        <option value="">Tutti gli obiettivi</option>
                        <option value="hypertrophy">Ipertrofia</option>
                        <option value="strength">Forza</option>
                        <option value="cut">Definizione</option>
                        <option value="recomp">Recomposizione</option>
                        <option value="peaking">Peaking</option>
                        <option value="general">Generale</option>
                    </select>
                    <select wire:model.live="active" class="form-control form-control-sm" style="width: 140px">
                        <option value="">Tutti</option>
                        <option value="1">Attivi</option>
                        <option value="0">Archiviati</option>
                    </select>
                </div>
                <a href="{{ route('backoffice.templates.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Nuovo template
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Obiettivo</th>
                        <th>Periodizzazione</th>
                        <th>Settimane</th>
                        <th>Giorni/sett.</th>
                        <th>Creatore</th>
                        <th>Stato</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($templates as $template)
                        <tr>
                            <td><strong>{{ $template->name }}</strong></td>
                            <td>
                                @php
                                    $goalBadge = match($template->goal) {
                                        'hypertrophy' => 'primary',
                                        'strength'    => 'danger',
                                        'cut'         => 'warning',
                                        'recomp'      => 'info',
                                        'peaking'     => 'dark',
                                        default       => 'secondary',
                                    };
                                    $goalLabel = match($template->goal) {
                                        'hypertrophy' => 'Ipertrofia',
                                        'strength'    => 'Forza',
                                        'cut'         => 'Definizione',
                                        'recomp'      => 'Recomposizione',
                                        'peaking'     => 'Peaking',
                                        default       => 'Generale',
                                    };
                                @endphp
                                <span class="badge badge-{{ $goalBadge }}">{{ $goalLabel }}</span>
                            </td>
                            <td>{{ str_replace('_', ' ', $template->periodization_model) }}</td>
                            <td>{{ $template->weeks_count }}</td>
                            <td>{{ $template->days_per_week }}</td>
                            <td>{{ $template->creator?->name ?? '—' }}</td>
                            <td>
                                @if ($template->is_active)
                                    <span class="badge badge-success">Attivo</span>
                                @else
                                    <span class="badge badge-secondary">Archiviato</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ route('backoffice.templates.builder', $template) }}" class="btn btn-xs btn-primary">
                                    <i class="fas fa-tools"></i> Apri builder
                                </a>
                                <button type="button"
                                        class="btn btn-xs btn-outline-secondary ml-1"
                                        wire:click="duplicate({{ $template->id }})"
                                        wire:loading.attr="disabled"
                                        wire:confirm="Duplicare '{{ $template->name }}'? Verrai reindirizzato al builder della copia.">
                                    <i class="fas fa-copy"></i> Duplica
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Nessun template trovato.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $templates->links() }}
        </div>
    </div>
</div>
