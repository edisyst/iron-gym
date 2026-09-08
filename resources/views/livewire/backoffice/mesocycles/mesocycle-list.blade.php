<div>
    <x-bo.filters>
        <div class="row">
            <div class="col-md-4">
                <label class="small">Cerca</label>
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control form-control-sm" placeholder="Cerca per nome...">
            </div>
            <div class="col-md-2">
                <label class="small">Status</label>
                <select wire:model.live="statusFilter" class="form-control form-control-sm">
                    <option value="">Tutti</option>
                    <option value="draft">Bozza</option>
                    <option value="active">Attivo</option>
                    <option value="completed">Completato</option>
                    <option value="aborted">Interrotto</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="small">Trainer</label>
                <select wire:model.live="trainerFilter" class="form-control form-control-sm">
                    <option value="">Tutti</option>
                    @foreach ($trainers as $trainer)
                        <option value="{{ $trainer->id }}">{{ $trainer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="small">Atleta</label>
                <select wire:model.live="athleteFilter" class="form-control form-control-sm">
                    <option value="">Tutti</option>
                    @foreach ($athletes as $athlete)
                        <option value="{{ $athlete->id }}">{{ $athlete->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-bo.filters>

    <x-bo.card bodyClass="p-0">
        <x-slot name="actions">
            <a href="{{ route('backoffice.mesocycles.assign') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i> Assegna mesociclo
            </a>
        </x-slot>

        <div class="table-responsive">
        <table class="table table-sm table-striped table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Atleta</th>
                    <th>Nome mesociclo</th>
                    <th>Obiettivo</th>
                    <th>Settimane</th>
                    <th>Data inizio</th>
                    <th>Status</th>
                    <th>Trainer</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($mesocycles as $meso)
                    <tr>
                        <td>{{ $meso->athlete?->name ?? '—' }}</td>
                        <td>{{ $meso->name }}</td>
                        <td>{{ $this->goalLabel($meso->goal) }}</td>
                        <td>{{ $meso->weeks_count }}</td>
                        <td>{{ $meso->start_date?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $this->statusBadgeClass($meso->status) }}">
                                {{ $this->statusLabel($meso->status) }}
                            </span>
                        </td>
                        <td>{{ $meso->trainer?->name ?? '—' }}</td>
                        <td>
                            <a href="{{ route('backoffice.mesocycles.show', $meso) }}" class="btn btn-sm btn-default" aria-label="Visualizza {{ $meso->name }}">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </a>
                            @if ($meso->athlete_id)
                                <a href="{{ route('backoffice.athletes.profile', ['athleteId' => $meso->athlete_id]) }}"
                                   class="btn btn-sm btn-outline-info ml-1">
                                    <i class="fas fa-user"></i> Profilo atleta
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-bo.empty :colspan="8">Nessun mesociclo trovato.</x-bo.empty>
                @endforelse
            </tbody>
        </table>
        </div>

        <x-slot name="footer">
            <x-bo.pagination :paginator="$mesocycles" />
        </x-slot>
    </x-bo.card>
</div>
