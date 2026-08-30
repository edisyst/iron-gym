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
    </div>

    {{-- Tab: Manuale --}}
    <div x-show="tab === 'manuale'" x-cloak>
        @livewire('backoffice.settings.manual-viewer')
    </div>

</div>
