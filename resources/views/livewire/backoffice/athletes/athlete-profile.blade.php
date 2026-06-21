<div x-data="{ tab: 'storico' }">
    {{-- Header atleta --}}
    <div class="card card-outline card-primary mb-3">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3">
                {{-- Avatar iniziali --}}
                <div style="width:56px;height:56px;border-radius:50%;background:#007bff;
                            display:flex;align-items:center;justify-content:center;
                            font-size:20px;font-weight:700;color:#fff;flex-shrink:0;">
                    {{ mb_strtoupper(mb_substr($athlete->member?->first_name ?? $athlete->name, 0, 1)) }}{{ mb_strtoupper(mb_substr($athlete->member?->last_name ?? '', 0, 1)) }}
                </div>
                <div>
                    <h4 class="mb-0">{{ $athleteName }}</h4>
                    <small class="text-muted">{{ $athlete->email }}</small>
                    <div class="mt-1">
                        @foreach ($athlete->getRoleNames() as $role)
                            <span class="badge badge-info">{{ $role }}</span>
                        @endforeach
                        @if ($activeMesocycle)
                            <span class="badge badge-success ml-1">
                                {{ $activeMesocycle->name }}
                                @if ($currentWeek)
                                    — Sett. {{ $currentWeek->week_number }}
                                @endif
                            </span>
                        @else
                            <span class="badge badge-secondary ml-1">Nessun mesociclo attivo</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab navigation --}}
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link" :class="{ active: tab === 'storico' }" href="#" @click.prevent="tab = 'storico'">
                <i class="fas fa-history mr-1"></i> Storico allenamenti
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" :class="{ active: tab === 'analytics' }" href="#" @click.prevent="tab = 'analytics'">
                <i class="fas fa-chart-line mr-1"></i> Analytics
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" :class="{ active: tab === 'misurazioni' }" href="#" @click.prevent="tab = 'misurazioni'">
                <i class="fas fa-ruler mr-1"></i> Misurazioni
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" :class="{ active: tab === 'landmarks' }" href="#" @click.prevent="tab = 'landmarks'">
                <i class="fas fa-mountain mr-1"></i> Volume landmarks
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" :class="{ active: tab === 'messaggi' }" href="#" @click.prevent="tab = 'messaggi'">
                <i class="fas fa-comments mr-1"></i> Messaggi
            </a>
        </li>
    </ul>

    {{-- Tab content --}}
    <div x-show="tab === 'storico'">
        @livewire('backoffice.athletes.athlete-session-history', ['athleteId' => $athleteId])
    </div>

    <div x-show="tab === 'analytics'" x-cloak>
        @livewire('backoffice.athletes.athlete-analytics', ['athleteId' => $athleteId])
    </div>

    <div x-show="tab === 'misurazioni'" x-cloak>
        @livewire('backoffice.athletes.body-measurement-form', ['athleteId' => $athleteId])
    </div>

    <div x-show="tab === 'landmarks'" x-cloak>
        @livewire('backoffice.mesocycles.volume-landmark-manager', ['athleteId' => $athleteId])
    </div>

    <div x-show="tab === 'messaggi'" x-cloak>
        @livewire('backoffice.messages.message-thread', ['athleteId' => $athleteId])
    </div>
</div>
