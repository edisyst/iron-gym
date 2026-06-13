<div>
    <div class="row">
        {{-- Colonna sinistra: informazioni esercizio --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informazioni</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <dl class="row mb-0">
                                <dt class="col-sm-5">Slug</dt>
                                <dd class="col-sm-7"><code>{{ $exercise->slug }}</code></dd>

                                <dt class="col-sm-5">Meccanica</dt>
                                <dd class="col-sm-7">
                                    @if ($exercise->mechanic === 'compound')
                                        <span class="badge badge-primary">Compound</span>
                                    @else
                                        <span class="badge badge-warning">Isolamento</span>
                                    @endif
                                </dd>

                                <dt class="col-sm-5">Piano</dt>
                                <dd class="col-sm-7">{{ ucfirst($exercise->plane) }}</dd>

                                <dt class="col-sm-5">Lateralità</dt>
                                <dd class="col-sm-7">{{ str_replace('_', ' ', $exercise->laterality) }}</dd>
                            </dl>
                        </div>
                        <div class="col-sm-6">
                            <dl class="row mb-0">
                                <dt class="col-sm-5">Livello</dt>
                                <dd class="col-sm-7">
                                    @if ($exercise->skill_level === 'beginner')
                                        <span class="badge badge-success">Principiante</span>
                                    @elseif ($exercise->skill_level === 'intermediate')
                                        <span class="badge badge-warning">Intermedio</span>
                                    @else
                                        <span class="badge badge-danger">Avanzato</span>
                                    @endif
                                </dd>

                                <dt class="col-sm-5">Misurazione</dt>
                                <dd class="col-sm-7">{{ str_replace('_', ' ', $exercise->measurement_type) }}</dd>

                                <dt class="col-sm-5">Creato da</dt>
                                <dd class="col-sm-7">{{ $exercise->creator?->name ?? 'Sistema' }}</dd>
                            </dl>
                        </div>
                    </div>

                    <hr>

                    {{-- Pattern motorio --}}
                    @php
                        $pattern   = $exercise->compoundPattern ?? $exercise->jointAction;
                        $isCompound = $exercise->compoundPattern !== null;
                    @endphp
                    <div class="mb-3">
                        <strong>Pattern motorio:</strong>
                        @if ($pattern)
                            {{ $pattern->name_it }}
                            @if ($isCompound)
                                <span class="badge badge-info ml-1">Compound pattern</span>
                            @else
                                <span class="badge badge-secondary ml-1">Joint action</span>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </div>

                    {{-- Descrizione --}}
                    @if ($exercise->description)
                        <div>
                            <strong>Descrizione:</strong>
                            <p class="mt-1">{{ $exercise->description }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Colonna destra: muscoli e attrezzatura --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Muscoli coinvolti</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Muscolo</th>
                                <th>Ruolo</th>
                                <th>Contributo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($exercise->muscles->sortBy(fn ($m) => match($m->pivot->role) { 'primary' => 0, 'secondary' => 1, default => 2 }) as $muscle)
                                <tr>
                                    <td>{{ $muscle->name_it }}</td>
                                    <td>
                                        @if ($muscle->pivot->role === 'primary')
                                            <span class="badge badge-success">Primary</span>
                                        @elseif ($muscle->pivot->role === 'secondary')
                                            <span class="badge badge-warning">Secondary</span>
                                        @else
                                            <span class="badge badge-secondary">Stabilizer</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $muscle->pivot->contribution_pct }}%
                                        <div style="width: 100%; background: #e9ecef; height: 8px; border-radius: 4px; margin-top: 3px;">
                                            <div style="width: {{ $muscle->pivot->contribution_pct }}%; background: #28a745; height: 8px; border-radius: 4px;"></div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Nessun muscolo</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Attrezzatura</h3>
                </div>
                <div class="card-body">
                    @forelse ($exercise->equipment as $eq)
                        <span class="badge badge-light border mr-1 mb-1">{{ $eq->name_it }}</span>
                    @empty
                        <span class="text-muted">—</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="mt-2">
        <a href="{{ route('backoffice.exercises.index') }}" class="btn btn-default btn-sm">
            <i class="fas fa-arrow-left"></i> Torna alla lista
        </a>
        <a href="{{ route('backoffice.exercises.edit', $exercise) }}" class="btn btn-primary btn-sm ml-2">
            <i class="fas fa-edit"></i> Modifica
        </a>
    </div>
</div>
