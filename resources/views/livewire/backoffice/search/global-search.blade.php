<div>
    <div class="row mb-3">
        <div class="col-md-6">
            <form method="GET" action="{{ route('backoffice.search') }}">
                <div class="input-group">
                    <input
                        type="search"
                        name="q"
                        class="form-control form-control-lg"
                        placeholder="Cerca atleti, PT, template, mesocicli..."
                        value="{{ $this->query }}"
                        autofocus
                    >
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i> Cerca
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if(mb_strlen(trim($this->query)) < 2)
        <div class="callout callout-info">
            <i class="fas fa-info-circle mr-1"></i>
            Inserisci almeno 2 caratteri per avviare la ricerca.
        </div>
    @else
        @php
            $totalResults = $athletes->count() + $trainers->count() + $templates->count() + $mesocycles->count();
        @endphp

        <p class="text-muted mb-3">
            <strong>{{ $totalResults }}</strong> risultat{{ $totalResults === 1 ? 'o' : 'i' }}
            per &ldquo;<em>{{ $this->query }}</em>&rdquo;
        </p>

        <div class="row">

            {{-- Atleti --}}
            <div class="col-md-6 mb-4">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-running mr-1"></i> Atleti
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-primary">{{ $athletes->count() }}</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if($athletes->isEmpty())
                            <p class="text-muted p-3 mb-0">Nessun atleta trovato.</p>
                        @else
                            <ul class="list-group list-group-flush">
                                @foreach($athletes as $member)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>{{ $member->full_name }}</strong>
                                            @if($member->email)
                                                <br><small class="text-muted">{{ $member->email }}</small>
                                            @endif
                                        </div>
                                        <a
                                            href="{{ route('backoffice.athletes.profile', $member->user_id) }}"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            <i class="fas fa-user mr-1"></i>Profilo
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Personal Trainer --}}
            <div class="col-md-6 mb-4">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-tie mr-1"></i> Personal Trainer
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-success">{{ $trainers->count() }}</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if($trainers->isEmpty())
                            <p class="text-muted p-3 mb-0">Nessun PT trovato.</p>
                        @else
                            <ul class="list-group list-group-flush">
                                @foreach($trainers as $trainer)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>{{ $trainer->name }}</strong>
                                            <br><small class="text-muted">{{ $trainer->email }}</small>
                                        </div>
                                        <a
                                            href="{{ route('backoffice.calendar.index') }}"
                                            class="btn btn-sm btn-outline-success"
                                        >
                                            <i class="fas fa-calendar mr-1"></i>Calendario
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Template schede --}}
            <div class="col-md-6 mb-4">
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-clipboard-list mr-1"></i> Template schede
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-warning">{{ $templates->count() }}</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if($templates->isEmpty())
                            <p class="text-muted p-3 mb-0">Nessun template trovato.</p>
                        @else
                            <ul class="list-group list-group-flush">
                                @foreach($templates as $template)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>{{ $template->name }}</strong>
                                            @if($template->description)
                                                <br><small class="text-muted">{{ Str::limit($template->description, 60) }}</small>
                                            @endif
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            @can('access-training-section')
                                                <a
                                                    href="{{ route('backoffice.templates.builder', $template) }}"
                                                    class="btn btn-outline-warning"
                                                >
                                                    <i class="fas fa-drafting-compass"></i>
                                                </a>
                                            @endcan
                                            <a
                                                href="{{ route('backoffice.templates.index') }}"
                                                class="btn btn-outline-secondary"
                                                title="Vai alla lista"
                                            >
                                                <i class="fas fa-list"></i>
                                            </a>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Mesocicli --}}
            <div class="col-md-6 mb-4">
                <div class="card card-outline card-danger">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-layer-group mr-1"></i> Mesocicli
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-danger">{{ $mesocycles->count() }}</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if($mesocycles->isEmpty())
                            <p class="text-muted p-3 mb-0">Nessun mesociclo trovato.</p>
                        @else
                            <ul class="list-group list-group-flush">
                                @foreach($mesocycles as $mesocycle)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>{{ $mesocycle->name }}</strong>
                                            @if($mesocycle->athlete)
                                                <br><small class="text-muted">
                                                    <i class="fas fa-user fa-xs mr-1"></i>{{ $mesocycle->athlete->name }}
                                                </small>
                                            @endif
                                        </div>
                                        @can('access-training-section')
                                            <a
                                                href="{{ route('backoffice.mesocycles.show', $mesocycle) }}"
                                                class="btn btn-sm btn-outline-danger"
                                            >
                                                <i class="fas fa-eye mr-1"></i>Dettaglio
                                            </a>
                                        @else
                                            <a
                                                href="{{ route('backoffice.mesocycles.index') }}"
                                                class="btn btn-sm btn-outline-secondary"
                                            >
                                                <i class="fas fa-list mr-1"></i>Lista
                                            </a>
                                        @endcan
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    @endif
</div>
