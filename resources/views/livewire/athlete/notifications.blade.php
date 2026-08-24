<div>
    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--ig-sp-4);">
        <h2 style="font-size:var(--ig-text-md);font-weight:700;color:var(--ig-text-1);margin:0;">
            Notifiche
            @if ($unreadCount > 0)
                <span class="ig-badge ig-badge--danger" style="margin-left:var(--ig-sp-2);">{{ $unreadCount }}</span>
            @endif
        </h2>
        @if ($unreadCount > 0)
            <button wire:click="markAllRead"
                    class="ig-btn ig-btn--secondary"
                    style="font-size:var(--ig-text-sm);padding:0 var(--ig-sp-3);height:36px;">
                Segna tutte come lette
            </button>
        @endif
    </div>

    @if ($notifications->isEmpty())
        <div style="text-align:center;padding:var(--ig-sp-10) var(--ig-sp-4);color:var(--ig-text-3);">
            <svg style="width:48px;height:48px;margin:0 auto var(--ig-sp-3);"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <p style="font-size:var(--ig-text-base);font-weight:600;margin:0 0 var(--ig-sp-1);">
                Nessuna notifica
            </p>
            <p style="font-size:var(--ig-text-sm);margin:0;">
                Le notifiche di sessione, corsi e promozioni waitlist appariranno qui.
            </p>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:var(--ig-sp-2);">
            @foreach ($notifications as $notification)
                @php
                    $data = $notification->data;
                    $type = $data['type'] ?? 'generic';
                    $isUnread = $notification->read_at === null;

                    $icon = match ($type) {
                        'session_reminder' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                        'waitlist_promoted' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                        'class_cancelled'  => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
                        default => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
                    };
                    $iconColor = match ($type) {
                        'session_reminder' => 'var(--ig-accent)',
                        'waitlist_promoted' => 'var(--ig-success)',
                        'class_cancelled'   => 'var(--ig-danger)',
                        default => 'var(--ig-text-3)',
                    };
                @endphp

                <div style="
                    background: {{ $isUnread ? 'var(--ig-surface-raised)' : 'var(--ig-surface)' }};
                    border: 1px solid {{ $isUnread ? 'var(--ig-accent)' : 'var(--ig-border)' }};
                    border-radius: var(--ig-radius-sm);
                    padding: var(--ig-sp-3) var(--ig-sp-4);
                    display: flex;
                    gap: var(--ig-sp-3);
                    align-items: flex-start;
                    position: relative;
                ">
                    {{-- Icona tipo --}}
                    <svg style="width:22px;height:22px;flex-shrink:0;color:{{ $iconColor }};margin-top:2px;"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                    </svg>

                    {{-- Corpo --}}
                    <div style="flex:1;min-width:0;">
                        <p style="font-size:var(--ig-text-sm);color:var(--ig-text-1);margin:0 0 2px;
                                   {{ $isUnread ? 'font-weight:600;' : '' }}">
                            {{ $data['message'] ?? 'Notifica' }}
                        </p>
                        <p style="font-size:var(--ig-text-xs);color:var(--ig-text-3);margin:0;">
                            {{ $notification->created_at->diffForHumans() }}
                        </p>
                    </div>

                    {{-- Azioni --}}
                    <div style="display:flex;gap:var(--ig-sp-2);flex-shrink:0;">
                        @if ($isUnread)
                            <button wire:click="markRead('{{ $notification->id }}')"
                                    style="background:none;border:none;padding:4px;cursor:pointer;color:var(--ig-text-3);"
                                    aria-label="Segna come letta">
                                <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24"
                                     stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        @endif
                        <button wire:click="deleteNotification('{{ $notification->id }}')"
                                style="background:none;border:none;padding:4px;cursor:pointer;color:var(--ig-text-3);"
                                aria-label="Elimina notifica">
                            <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <div style="margin-top:var(--ig-sp-4);">
            {{ $notifications->links() }}
        </div>
    @endif
</div>
