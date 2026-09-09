<div>
    <x-bo.filters>
        <div class="row">
            <div class="col-md-4">
                <label class="small">Cerca</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cerca template..." class="form-control form-control-sm">
            </div>
            <div class="col-md-4">
                <label class="small">Obiettivo</label>
                <select wire:model.live="goal" class="form-control form-control-sm">
                    <option value="">Tutti</option>
                    <option value="hypertrophy">Ipertrofia</option>
                    <option value="strength">Forza</option>
                    <option value="cut">Definizione</option>
                    <option value="recomp">Recomposizione</option>
                    <option value="peaking">Peaking</option>
                    <option value="general">Generale</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small">Stato</label>
                <select wire:model.live="active" class="form-control form-control-sm">
                    <option value="">Tutti</option>
                    <option value="1">Attivi</option>
                    <option value="0">Archiviati</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button wire:click="resetFilters" class="btn btn-default btn-sm">
                    <i class="fas fa-times mr-1"></i> Azzera filtri
                </button>
            </div>
        </div>
    </x-bo.filters>

    <x-bo.card bodyClass="p-0">
        <x-slot name="actions">
            <a href="{{ route('backoffice.templates.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Nuovo template
            </a>
        </x-slot>

        <div class="table-responsive">
        <table class="table table-sm table-striped table-hover mb-0">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Obiettivo</th>
                    <th>Periodizzazione</th>
                    <th>Settimane</th>
                    <th>Giorni/sett.</th>
                    <th>Creatore</th>
                    <th>Stato</th>
                    <th class="text-right table-actions">Azioni</th>
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
                            <a href="{{ route('backoffice.templates.builder', $template) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-tools"></i> Apri builder
                            </a>
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary ml-1"
                                    wire:click="duplicate({{ $template->id }})"
                                    wire:loading.attr="disabled"
                                    wire:confirm="Duplicare '{{ $template->name }}'? Verrai reindirizzato al builder della copia.">
                                <i class="fas fa-copy"></i> Duplica
                            </button>
                        </td>
                    </tr>
                @empty
                    <x-bo.empty :colspan="8">Nessun template trovato.</x-bo.empty>
                @endforelse
            </tbody>
        </table>
        </div>

        <x-slot name="footer">
            <x-bo.pagination :paginator="$templates" />
        </x-slot>
    </x-bo.card>
</div>
