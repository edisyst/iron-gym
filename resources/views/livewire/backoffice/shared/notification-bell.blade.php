<div
    x-data="{ open: false }"
    class="d-inline-block"
    wire:poll.10s
>
    <div class="position-relative d-inline-block">
        <button
            @click="open = !open"
            class="btn btn-sm btn-outline-secondary"
            title="Notifiche"
        >
            <i class="fas fa-bell"></i>
            @if ($unreadCount > 0)
                <span class="badge badge-danger badge-pill ml-1">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
            @endif
        </button>

        <div
            x-show="open"
            @click.outside="open = false"
            x-cloak
            class="dropdown-menu dropdown-menu-right show shadow"
            style="min-width: 320px; max-height: 420px; overflow-y: auto;"
        >
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                <span class="font-weight-bold text-sm">Notifiche</span>
                @if ($unreadCount > 0)
                    <button
                        wire:click="markAllAsRead"
                        class="btn btn-sm btn-link p-0 text-muted"
                    >Segna tutte come lette</button>
                @endif
            </div>

            @forelse ($notifications as $notification)
                <div
                    wire:click="markAsRead('{{ $notification->id }}')"
                    class="dropdown-item py-2 {{ $notification->read_at ? '' : 'bg-light' }}"
                    style="white-space: normal; cursor: pointer;"
                >
                    <div class="d-flex align-items-start">
                        @if (! $notification->read_at)
                            <span class="badge badge-primary badge-pill mr-2 mt-1" style="width:8px;height:8px;padding:0;"></span>
                        @else
                            <span class="mr-2" style="width:8px;"></span>
                        @endif
                        <div>
                            <div class="text-sm">{{ $notification->data['message'] ?? $notification->type }}</div>
                            <div class="text-xs text-muted">{{ $notification->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="dropdown-item text-muted text-sm">Nessuna notifica</div>
            @endforelse
        </div>
    </div>
</div>
