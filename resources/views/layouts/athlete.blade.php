<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#FF6B00">
    {{-- Imposta il tema prima del rendering per evitare flash --}}
    <script>
    (function(){
        var s = localStorage.getItem('ig-theme');
        var m = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
        document.documentElement.setAttribute('data-theme', s || (m ? 'light' : 'dark'));
    })();
    </script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <title>{{ config('app.name', 'Iron Gym') }}</title>
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/athlete.css') }}">
    @stack('styles')
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
                Home
            </a>
            <a href="{{ route('athlete.history') }}"
               class="{{ request()->routeIs('athlete.history', 'athlete.progress', 'athlete.session', 'athlete.session.recap') ? 'active' : '' }}"
               aria-current="{{ request()->routeIs('athlete.history', 'athlete.progress', 'athlete.session', 'athlete.session.recap') ? 'page' : 'false' }}">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                             M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Allenamento
            </a>
            <a href="{{ route('athlete.volume') }}"
               class="{{ request()->routeIs('athlete.volume', 'athlete.records', 'athlete.measurements', 'athlete.photos.*') ? 'active' : '' }}"
               aria-current="{{ request()->routeIs('athlete.volume', 'athlete.records', 'athlete.measurements', 'athlete.photos.*') ? 'page' : 'false' }}">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22
                             m0 0l-3.714-.857m3.714.857l-.857 3.714"/>
                </svg>
                Progressi
            </a>
            <a href="{{ route('athlete.records') }}"
               class="{{ request()->routeIs('athlete.records') ? 'active' : '' }}"
               aria-current="{{ request()->routeIs('athlete.records') ? 'page' : 'false' }}"
               style="padding-left:2.5rem;">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
                Record
            </a>
            <a href="{{ route('athlete.exercises.index') }}"
               class="{{ request()->routeIs('athlete.exercises*') ? 'active' : '' }}"
               aria-current="{{ request()->routeIs('athlete.exercises*') ? 'page' : 'false' }}"
               style="padding-left:2.5rem;">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                Esercizi
            </a>
            <a href="{{ route('athlete.profile') }}"
               class="{{ request()->routeIs('athlete.profile', 'athlete.messages', 'athlete.bookings') ? 'active' : '' }}"
               aria-current="{{ request()->routeIs('athlete.profile', 'athlete.messages', 'athlete.bookings') ? 'page' : 'false' }}">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                </svg>
                Profilo
                <span x-show="$store.messages.unread > 0"
                      x-text="$store.messages.unread > 9 ? '9+' : $store.messages.unread"
                      class="nav-unread-badge sidenav-badge"
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

    <header class="app-topbar"
            x-data="{
                theme: document.documentElement.getAttribute('data-theme') || 'dark',
                toggle() {
                    this.theme = this.theme === 'dark' ? 'light' : 'dark';
                    document.documentElement.setAttribute('data-theme', this.theme);
                    localStorage.setItem('ig-theme', this.theme);
                }
            }">
        <span class="app-brand">Iron Gym</span>
        <a href="{{ route('athlete.profile') }}" class="user-name">{{ auth()->user()->name }}</a>
        <button @click="toggle()" class="ig-theme-toggle"
                :aria-label="theme === 'dark' ? 'Tema chiaro' : 'Tema scuro'">
            <svg x-show="theme === 'dark'" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="12" r="5"/>
                <path stroke-linecap="round" d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
            </svg>
            <svg x-show="theme === 'light'" x-cloak width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
            </svg>
        </button>
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

    <x-athlete.bottom-nav />
    <x-athlete.toast />

    {{-- Toast PR --}}
    <div
        x-data="{ show: false, exerciseName: '', e1rm: '' }"
        x-on:pr-achieved.window="exerciseName = $event.detail.exerciseName; e1rm = $event.detail.e1rm; show = true; setTimeout(() => show = false, 4000)"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        style="position:fixed; bottom:80px; left:50%; transform:translateX(-50%); z-index:1060; display:none; min-width:260px; max-width:90vw;"
        role="status"
        aria-live="polite"
    >
        <div style="background:var(--ig-surface);border:1px solid var(--ig-accent);border-radius:var(--ig-radius-lg);
                    padding:var(--ig-sp-3) var(--ig-sp-4);display:flex;align-items:center;gap:var(--ig-sp-3);
                    box-shadow:0 4px 24px rgba(0,0,0,0.5);">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                 aria-hidden="true" style="color:var(--ig-accent);flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
            </svg>
            <div>
                <span style="font-weight:700;color:var(--ig-accent);font-size:var(--ig-text-sm);">Nuovo PR!</span>
                <span style="color:var(--ig-text-1);font-size:var(--ig-text-sm);margin-left:4px;" x-text="exerciseName"></span>
                <span style="color:var(--ig-text-2);font-size:var(--ig-text-sm);"> &mdash; </span>
                <span style="color:var(--ig-text-1);font-size:var(--ig-text-sm);font-weight:600;" x-text="e1rm + ' kg e1RM'"></span>
            </div>
        </div>
    </div>

    @stack('scripts')
    @livewireScripts
    <script>
    // Rete assente — toast di errore
    document.addEventListener('livewire:request-failed', function () {
        window.dispatchEvent(new CustomEvent('toast', {
            detail: { message: 'Connessione assente — riprova', type: 'error' }
        }));
    });

    document.addEventListener('alpine:init', function () {
        Alpine.store('messages', {
            unread: 0,
            init: function () {
                var self = this;
                fetch('/athlete/messages-unread-count')
                    .then(function (r) { return r.ok ? r.json() : { count: 0 }; })
                    .then(function (d) { self.unread = d.count ?? 0; })
                    .catch(function () {});
            },
        });
    });
    </script>

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
