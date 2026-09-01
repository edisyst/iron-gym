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
        @livewire('backoffice.settings.feature-flag-manager')

        <div class="card card-outline card-secondary mt-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plug mr-1"></i> Documentazione API</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Riferimento interattivo e specifica OpenAPI 3.0 per le integrazioni esterne.</p>
                <a href="{{ route('backoffice.settings.api-docs') }}" target="_blank" class="btn btn-outline-primary btn-sm mr-2">
                    <i class="fas fa-code mr-1"></i> API Reference (Swagger UI)
                </a>
                <a href="{{ route('backoffice.settings.api-docs.yaml') }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-file-code mr-1"></i> Scarica OpenAPI YAML
                </a>
            </div>
        </div>
    </div>

    {{-- Tab: Manuale --}}
    <div x-show="tab === 'manuale'" x-cloak>
        @livewire('backoffice.settings.manual-viewer')
    </div>

</div>
