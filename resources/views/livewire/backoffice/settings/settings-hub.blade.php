<div x-data="{ tab: 'funzioni' }">

    {{-- Tab navigation --}}
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link" :class="{ active: tab === 'funzioni' }" href="#" @click.prevent="tab = 'funzioni'">
                <i class="fas fa-toggle-on mr-1"></i> Funzioni
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" :class="{ active: tab === 'manuale' }" href="#" @click.prevent="tab = 'manuale'">
                <i class="fas fa-book mr-1"></i> Manuale
            </a>
        </li>
    </ul>

    {{-- Tab: Funzioni --}}
    <div x-show="tab === 'funzioni'">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Attivazione funzioni</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Attiva o disattiva le funzionalita' della piattaforma per tutti gli utenti.
                    Le modifiche hanno effetto immediato.
                </p>
                <a href="{{ route('backoffice.settings.feature-flags') }}" class="btn btn-primary">
                    <i class="fas fa-toggle-on mr-1"></i> Gestisci funzioni
                </a>
            </div>
        </div>
    </div>

    {{-- Tab: Manuale --}}
    <div x-show="tab === 'manuale'" x-cloak>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Manuale operativo</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">In preparazione.</p>
            </div>
        </div>
    </div>

</div>
