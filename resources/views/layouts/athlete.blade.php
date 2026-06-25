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
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: #121212;
            color: #FFFFFF;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            padding-bottom: 72px; /* spazio per la bottom nav */
            padding-top: 48px;   /* spazio per la top bar */
        }
        .app-topbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 48px;
            background-color: #1E1E1E;
            border-bottom: 1px solid #2A2A2A;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            z-index: 1000;
        }
        .app-topbar .app-brand {
            font-size: 16px;
            font-weight: 700;
            color: #FF6B00;
            letter-spacing: 0.02em;
        }
        .app-topbar .user-name {
            font-size: 13px;
            color: #aaa;
        }
        .app-topbar .logout-btn {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 6px 8px;
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            text-decoration: none;
            transition: color 0.15s;
        }
        .app-topbar .logout-btn:hover { color: #ef4444; }
        .app-main {
            max-width: 600px;
            margin: 0 auto;
            padding: 16px 16px 0;
        }
        /* Bottom navigation bar */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 64px;
            background-color: #1E1E1E;
            border-top: 1px solid #2A2A2A;
            display: flex;
            align-items: stretch;
            z-index: 1000;
        }
        .bottom-nav a {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #888888;
            text-decoration: none;
            font-size: 10px;
            gap: 4px;
            transition: color 0.15s ease;
        }
        .bottom-nav a.active,
        .bottom-nav a:hover {
            color: #FF6B00;
        }
        .bottom-nav svg {
            width: 24px;
            height: 24px;
        }
        /* Card atleta */
        .athlete-card {
            background-color: #1E1E1E;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
        }
        .athlete-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-accent { background-color: #FF6B00; color: #fff; }
        .badge-gray   { background-color: #333; color: #aaa; }
        .badge-green  { background-color: #22c55e; color: #fff; }
        .badge-red    { background-color: #ef4444; color: #fff; }
        /* Pulsanti */
        .btn-accent {
            background-color: #FF6B00;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.15s ease;
        }
        .btn-accent:hover { background-color: #e05e00; }
        .btn-ghost {
            background: transparent;
            color: #888;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 14px;
            cursor: pointer;
        }
        /* Input allenamento */
        .workout-input {
            background-color: #2A2A2A;
            border: 1px solid #333;
            border-radius: 6px;
            color: #fff;
            padding: 8px 10px;
            font-size: 15px;
            width: 72px;
            text-align: center;
        }
        .workout-input:focus {
            outline: none;
            border-color: #FF6B00;
        }
        /* Status sessione */
        .status-planned   { color: #888; }
        .status-in_progress { color: #FF6B00; }
        .status-completed { color: #22c55e; }
        .status-skipped   { color: #ef4444; }
        /* Separatore sezione */
        .section-title {
            color: #888;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        /* Radio metriche feedback */
        .metric-row { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
        .metric-label { flex: 1; color: #ccc; font-size: 14px; }
        .metric-options { display: flex; gap: 8px; }
        .metric-options label {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: 1px solid #333;
            cursor: pointer;
            font-size: 14px;
            color: #aaa;
        }
        .metric-options input[type="radio"] { display: none; }
        .metric-options input[type="radio"]:checked + span {
            background-color: #FF6B00;
            color: #fff;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <header class="app-topbar">
        <span class="app-brand">Iron Gym</span>
        <span class="user-name">{{ auth()->user()->name }}</span>
        <a href="{{ route('logout') }}" class="logout-btn"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Esci
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none">
            @csrf
        </form>
    </header>

    <main class="app-main">
        {{ $slot }}
    </main>

    {{-- Bottom navigation --}}
    <nav class="bottom-nav">
        {{-- Oggi / Dashboard --}}
        <a href="{{ route('athlete.dashboard') }}"
           class="{{ request()->routeIs('athlete.dashboard') ? 'active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 12l2-2m0 0l7-7 7 7m-14 0v8a1 1 0 001 1h4v-5h4v5h4a1 1 0 001-1v-8"/>
            </svg>
            <span>Oggi</span>
        </a>

        {{-- Storico + Progressi --}}
        <a href="{{ route('athlete.history') }}"
           class="{{ request()->routeIs('athlete.history') || request()->routeIs('athlete.progress') ? 'active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                         M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <span>Storico</span>
        </a>

        {{-- Esercizi --}}
        <a href="{{ route('athlete.exercises.index') }}"
           class="{{ request()->routeIs('athlete.exercises*') ? 'active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
            <span>Esercizi</span>
        </a>

        {{-- Prenota --}}
        <a href="{{ route('athlete.bookings') }}"
           class="{{ request()->routeIs('athlete.bookings') ? 'active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span>Prenota</span>
        </a>

        {{-- Messaggi --}}
        <a href="{{ route('athlete.messages') }}"
           class="{{ request()->routeIs('athlete.messages') ? 'active' : '' }}"
           style="position: relative;"
           x-data="{ unread: 0 }"
           x-init="
               fetch('/athlete/messages-unread-count')
                   .then(r => r.ok ? r.json() : {count:0})
                   .then(d => unread = d.count ?? 0)
                   .catch(() => {})
           "
        >
            <span style="position: relative; display: inline-flex;">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <span
                    x-show="unread > 0"
                    x-text="unread > 9 ? '9+' : unread"
                    style="
                        position: absolute;
                        top: -6px; right: -8px;
                        background: #ef4444;
                        color: #fff;
                        border-radius: 999px;
                        font-size: 9px;
                        min-width: 16px;
                        height: 16px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        padding: 0 3px;
                    "
                ></span>
            </span>
            <span>Messaggi</span>
        </a>

        {{-- Profilo --}}
        <a href="{{ route('athlete.profile') }}"
           class="{{ request()->routeIs('athlete.profile') ? 'active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span>Profilo</span>
        </a>
    </nav>

    @stack('scripts')
    @livewireScripts

    @livewire('shared.in-app-feedback')

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
