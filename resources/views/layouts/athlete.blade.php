<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#FF6B00">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <title>{{ config('app.name', 'Iron Gym') }}</title>
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/athlete.css') }}">
</head>
<body>
    {{-- Sidebar navigation (desktop ≥ 1024px) --}}
    <aside class="app-sidenav" aria-label="Navigazione principale">
        <div class="sidenav-brand">Iron Gym</div>
        <div class="sidenav-user">{{ auth()->user()->name ?? '' }}</div>
        <nav>
            <a href="{{ route('athlete.dashboard') }}"
               class="{{ request()->routeIs('athlete.dashboard') ? 'active' : '' }}"
               aria-current="{{ request()->routeIs('athlete.dashboard') ? 'page' : 'false' }}">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3 12l2-2m0 0l7-7 7 7m-14 0v8a1 1 0 001 1h4v-5h4v5h4a1 1 0 001-1v-8"/>
                </svg>
                Oggi
            </a>
            <a href="{{ route('athlete.history') }}"
               class="{{ request()->routeIs('athlete.history') || request()->routeIs('athlete.progress') ? 'active' : '' }}"
               aria-current="{{ request()->routeIs('athlete.history') || request()->routeIs('athlete.progress') ? 'page' : 'false' }}">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                             M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Storico
            </a>
            <a href="{{ route('athlete.exercises.index') }}"
               class="{{ request()->routeIs('athlete.exercises*') ? 'active' : '' }}"
               aria-current="{{ request()->routeIs('athlete.exercises*') ? 'page' : 'false' }}">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                Esercizi
            </a>
            <a href="{{ route('athlete.bookings') }}"
               class="{{ request()->routeIs('athlete.bookings') ? 'active' : '' }}"
               aria-current="{{ request()->routeIs('athlete.bookings') ? 'page' : 'false' }}">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Prenota
            </a>
            <a href="{{ route('athlete.messages') }}"
               class="{{ request()->routeIs('athlete.messages') ? 'active' : '' }}"
               aria-current="{{ request()->routeIs('athlete.messages') ? 'page' : 'false' }}"
               x-data="{ unread: 0 }"
               x-init="
                   fetch('/athlete/messages-unread-count')
                       .then(r => r.ok ? r.json() : {count:0})
                       .then(d => unread = d.count ?? 0)
                       .catch(() => {})
               ">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                Messaggi
                <span x-show="unread > 0" x-text="unread > 9 ? '9+' : unread"
                      class="nav-unread-badge" style="position:static; margin-left:auto;"
                      aria-live="polite"></span>
            </a>
        </nav>
        <div class="sidenav-footer">
            <a href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form-side').submit();">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Esci
            </a>
            <form id="logout-form-side" action="{{ route('logout') }}" method="POST" style="display:none">@csrf</form>
        </div>
    </aside>

    <header class="app-topbar">
        <span class="app-brand">Iron Gym</span>
        <a href="{{ route('athlete.profile') }}" class="user-name">{{ auth()->user()->name }}</a>
        <a href="{{ route('logout') }}" class="logout-btn"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
           aria-label="Esci dall'applicazione">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Esci
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none">
            @csrf
        </form>
    </header>

    <main class="app-main" id="athlete-main-content">
        {{ $slot }}
    </main>

    {{-- Bottom navigation (mobile / tablet) --}}
    <nav class="bottom-nav" aria-label="Navigazione principale">
        <a href="{{ route('athlete.dashboard') }}"
           class="{{ request()->routeIs('athlete.dashboard') ? 'active' : '' }}"
           aria-current="{{ request()->routeIs('athlete.dashboard') ? 'page' : 'false' }}"
           aria-label="Oggi">
            <span class="nav-pill">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3 12l2-2m0 0l7-7 7 7m-14 0v8a1 1 0 001 1h4v-5h4v5h4a1 1 0 001-1v-8"/>
                </svg>
            </span>
            <span>Oggi</span>
        </a>

        <a href="{{ route('athlete.history') }}"
           class="{{ request()->routeIs('athlete.history') || request()->routeIs('athlete.progress') ? 'active' : '' }}"
           aria-current="{{ request()->routeIs('athlete.history') || request()->routeIs('athlete.progress') ? 'page' : 'false' }}"
           aria-label="Storico sessioni">
            <span class="nav-pill">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                             M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </span>
            <span>Storico</span>
        </a>

        <a href="{{ route('athlete.exercises.index') }}"
           class="{{ request()->routeIs('athlete.exercises*') ? 'active' : '' }}"
           aria-current="{{ request()->routeIs('athlete.exercises*') ? 'page' : 'false' }}"
           aria-label="Catalogo esercizi">
            <span class="nav-pill">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
            </span>
            <span>Esercizi</span>
        </a>

        <a href="{{ route('athlete.bookings') }}"
           class="{{ request()->routeIs('athlete.bookings') ? 'active' : '' }}"
           aria-current="{{ request()->routeIs('athlete.bookings') ? 'page' : 'false' }}"
           aria-label="Prenota sessione PT o corso">
            <span class="nav-pill">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </span>
            <span>Prenota</span>
        </a>

        <a href="{{ route('athlete.messages') }}"
           class="{{ request()->routeIs('athlete.messages') ? 'active' : '' }}"
           aria-current="{{ request()->routeIs('athlete.messages') ? 'page' : 'false' }}"
           aria-label="Messaggi dal trainer"
           x-data="{ unread: 0 }"
           x-init="
               fetch('/athlete/messages-unread-count')
                   .then(r => r.ok ? r.json() : {count:0})
                   .then(d => unread = d.count ?? 0)
                   .catch(() => {})
           ">
            <span class="nav-pill">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <span
                    x-show="unread > 0"
                    x-text="unread > 9 ? '9+' : unread"
                    class="nav-unread-badge"
                    aria-live="polite"
                ></span>
            </span>
            <span>Messaggi</span>
        </a>
    </nav>

    @stack('scripts')
    @livewireScripts

    @if(config('features.in_app_feedback_enabled'))
        @livewire('shared.in-app-feedback')
    @endif

    {{-- Registrazione service worker e permesso push --}}
    @auth
    @feature('push_notifications')
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').then(function (registration) {
                if (!('PushManager' in window)) return;

                Notification.requestPermission().then(function (permission) {
                    if (permission !== 'granted') return;

                    const vapidPublicKey = '{{ config("services.vapid.public_key") }}';
                    if (!vapidPublicKey) return;

                    registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: vapidPublicKey,
                    }).then(function (subscription) {
                        fetch('{{ route("athlete.push-subscribe") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: JSON.stringify(subscription.toJSON()),
                        });
                    });
                });
            });
        }
    </script>
    @endfeature
    @endauth
</body>
</html>
