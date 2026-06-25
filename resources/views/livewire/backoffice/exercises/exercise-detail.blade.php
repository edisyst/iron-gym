<div>
    {{-- Breadcrumb --}}
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('backoffice.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('backoffice.exercises.index') }}">Esercizi</a></li>
        <li class="breadcrumb-item active">{{ $exercise->name_it }}</li>
    </ol>
    <div class="clearfix mb-3"></div>

    {{-- Azioni header --}}
    <div class="mb-3 d-flex align-items-center justify-content-between">
        <h4 class="mb-0">{{ $exercise->name_it }}</h4>
        <div>
            <a href="{{ route('backoffice.exercises.index') }}" class="btn btn-default btn-sm">
                <i class="fas fa-arrow-left"></i> Lista
            </a>
            <a href="{{ route('backoffice.exercises.edit', $exercise) }}" class="btn btn-primary btn-sm ml-1">
                <i class="fas fa-edit"></i> Modifica
            </a>
        </div>
    </div>

    <div class="row">
        {{-- Colonna sinistra --}}
        <div class="col-lg-8">

            {{-- Immagine placeholder --}}
            <div class="card card-outline card-secondary">
                <div class="card-body p-0 text-center"
                     style="min-height: 220px; display:flex; align-items:center; justify-content:center; background:#f4f6f9;">
                    @php
                        $imgUrl = null;
                        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
                            if (file_exists(public_path("images/exercises/{$exercise->slug}.{$ext}"))) {
                                $imgUrl = asset("images/exercises/{$exercise->slug}.{$ext}");
                                break;
                            }
                        }
                    @endphp
                    @if ($imgUrl)
                        <img src="{{ $imgUrl }}"
                             alt="{{ $exercise->name_it }}"
                             class="img-fluid"
                             style="max-height: 300px; object-fit: contain;">
                    @else
                        <div class="text-muted text-center py-5">
                            <i class="fas fa-image fa-3x mb-2 d-block" style="opacity:.3"></i>
                            <small>Immagine non disponibile</small>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Card: Identità e Classificazione --}}
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-tags mr-1"></i> Identità e Classificazione</h3>
                </div>
                <div class="card-body">
                    @php
                        $pattern    = $exercise->compoundPattern ?? $exercise->jointAction;
                        $isCompound = $exercise->compoundPattern !== null;
                    @endphp
                    <dl class="row mb-0">
                        <dt class="col-sm-4">ID Sistema</dt>
                        <dd class="col-sm-8"><code>{{ $exercise->slug }}</code></dd>

                        <dt class="col-sm-4">Pattern motorio</dt>
                        <dd class="col-sm-8">
                            @if ($pattern)
                                {{ $pattern->name_it }}
                                <span class="badge {{ $isCompound ? 'badge-info' : 'badge-secondary' }} ml-1">
                                    {{ $isCompound ? 'Compound' : 'Isolation' }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Meccanica</dt>
                        <dd class="col-sm-8">
                            @if ($exercise->mechanic === 'compound')
                                <span class="badge badge-primary">Multi-articolare</span>
                            @else
                                <span class="badge badge-warning">Mono-articolare</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Livello</dt>
                        <dd class="col-sm-8">
                            @if ($exercise->skill_level === 'beginner')
                                <span class="badge badge-success">Principiante</span>
                            @elseif ($exercise->skill_level === 'intermediate')
                                <span class="badge badge-warning">Intermedio</span>
                            @else
                                <span class="badge badge-danger">Avanzato</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Piano di movimento</dt>
                        <dd class="col-sm-8">
                            @php
                                $planeLabel = match($exercise->plane) {
                                    'sagittal'    => 'Sagittale',
                                    'frontal'     => 'Frontale',
                                    'transverse'  => 'Trasversale',
                                    'multiplanar' => 'Multipiano',
                                    default       => ucfirst($exercise->plane),
                                };
                            @endphp
                            {{ $planeLabel }}
                        </dd>

                        <dt class="col-sm-4">Lateralità</dt>
                        <dd class="col-sm-8">
                            @php
                                $lateralityLabel = match($exercise->laterality) {
                                    'bilateral'               => 'Bilaterale',
                                    'unilateral_alternating'  => 'Unilaterale alternato',
                                    'unilateral_isolated'     => 'Unilaterale isolato',
                                    default                   => str_replace('_', ' ', $exercise->laterality),
                                };
                            @endphp
                            {{ $lateralityLabel }}
                        </dd>

                        <dt class="col-sm-4">Tipo misurazione</dt>
                        <dd class="col-sm-8">
                            @php
                                $measurementLabel = match($exercise->measurement_type) {
                                    'reps_weight' => 'Reps & Weight',
                                    'reps_only'   => 'Solo ripetizioni',
                                    'time'        => 'Tempo',
                                    'distance'    => 'Distanza',
                                    default       => str_replace('_', ' ', $exercise->measurement_type),
                                };
                            @endphp
                            {{ $measurementLabel }}
                        </dd>

                        @if ($exercise->creator)
                            <dt class="col-sm-4">Creato da</dt>
                            <dd class="col-sm-8">{{ $exercise->creator->name }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Card: Descrizione esecuzione --}}
            @if ($exercise->execution_description || $exercise->description)
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-clipboard-list mr-1"></i> Descrizione esecuzione</h3>
                    </div>
                    <div class="card-body">
                        @if ($exercise->execution_description)
                            <p class="mb-0" style="white-space: pre-line;">{{ $exercise->execution_description }}</p>
                        @elseif ($exercise->description)
                            <p class="mb-0">{{ $exercise->description }}</p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Card: Video tecnico --}}
            @if ($exercise->video_url)
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-play-circle mr-1"></i> Video tecnico</h3>
                    </div>
                    <div class="card-body">
                        <a href="{{ $exercise->video_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-external-link-alt mr-1"></i> Guarda il video
                        </a>
                    </div>
                </div>
            @endif

        </div>

        {{-- Colonna destra --}}
        <div class="col-lg-4">

            {{-- Card: Attrezzatura --}}
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-dumbbell mr-1"></i> Attrezzatura</h3>
                </div>
                <div class="card-body">
                    @forelse ($exercise->equipment as $eq)
                        <span class="badge badge-light border mr-1 mb-1" style="font-size:.85em">{{ $eq->name_it }}</span>
                    @empty
                        <span class="text-muted">Nessuna attrezzatura</span>
                    @endforelse
                </div>
            </div>

            {{-- Card: Coinvolgimento Muscolare e Volume --}}
            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-male mr-1"></i> Coinvolgimento Muscolare</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Muscolo</th>
                                <th>Ruolo</th>
                                <th style="width:90px">Contributo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $roleOrder = ['primary' => 0, 'secondary' => 1, 'stabilizer' => 2];
                                $sortedMuscles = $exercise->muscles->sortBy([
                                    fn ($a, $b) => ($roleOrder[$a->pivot->role] ?? 9) <=> ($roleOrder[$b->pivot->role] ?? 9),
                                    fn ($a, $b) => $b->pivot->contribution_pct <=> $a->pivot->contribution_pct,
                                ]);
                            @endphp
                            @forelse ($sortedMuscles as $muscle)
                                @php
                                    $roleLabel = match($muscle->pivot->role) {
                                        'primary'    => 'Primario',
                                        'secondary'  => 'Secondario',
                                        'stabilizer' => 'Stabilizzatore',
                                        default      => ucfirst($muscle->pivot->role),
                                    };
                                    $barClass = match($muscle->pivot->role) {
                                        'primary'    => 'bg-danger',
                                        'secondary'  => 'bg-warning',
                                        'stabilizer' => 'bg-info',
                                        default      => 'bg-secondary',
                                    };
                                @endphp
                                <tr>
                                    <td class="align-middle">{{ $muscle->name_it }}</td>
                                    <td class="align-middle">
                                        <span class="badge badge-{{ $muscle->pivot->role === 'primary' ? 'danger' : ($muscle->pivot->role === 'secondary' ? 'warning' : 'info') }}">
                                            {{ $roleLabel }}
                                        </span>
                                    </td>
                                    <td class="align-middle">
                                        <div class="d-flex align-items-center">
                                            <small class="mr-1" style="min-width:28px">{{ $muscle->pivot->contribution_pct }}%</small>
                                            <div class="progress flex-fill" style="height:8px">
                                                <div class="progress-bar {{ $barClass }}"
                                                     role="progressbar"
                                                     style="width: {{ $muscle->pivot->contribution_pct }}%"
                                                     aria-valuenow="{{ $muscle->pivot->contribution_pct }}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100">
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">Nessun muscolo</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
