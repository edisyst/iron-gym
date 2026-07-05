{{-- Bottom navigation — 4 tab fissi. Nascosta durante sessione live (UX02 @push styles). --}}
<nav class="bottom-nav" aria-label="Navigazione principale">

    {{-- Home --}}
    <a href="{{ route('athlete.dashboard') }}"
       class="{{ request()->routeIs('athlete.dashboard') ? 'active' : '' }}"
       aria-current="{{ request()->routeIs('athlete.dashboard') ? 'page' : 'false' }}"
       aria-label="Home">
        <span class="nav-pill">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 12l2-2m0 0l7-7 7 7m-14 0v8a1 1 0 001 1h4v-5h4v5h4a1 1 0 001-1v-8"/>
            </svg>
        </span>
        <span>Home</span>
    </a>

    {{-- Allenamento --}}
    <a href="{{ route('athlete.history') }}"
       class="{{ request()->routeIs('athlete.history', 'athlete.progress', 'athlete.session', 'athlete.session.recap') ? 'active' : '' }}"
       aria-current="{{ request()->routeIs('athlete.history', 'athlete.progress', 'athlete.session', 'athlete.session.recap') ? 'page' : 'false' }}"
       aria-label="Allenamento">
        <span class="nav-pill">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M4.5 12a7.5 7.5 0 0015 0m-15 0a7.5 7.5 0 1115 0M4.5 12H3m16.5 0H21
                         m-6.75-6.75l-1.06 1.06M6.75 17.25l-1.06 1.06M17.25 17.25l1.06 1.06
                         M7.81 6.81L6.75 5.75M12 3v1.5m0 15V21"/>
            </svg>
        </span>
        <span>Allenamento</span>
    </a>

    {{-- Progressi --}}
    <a href="{{ route('athlete.volume') }}"
       class="{{ request()->routeIs('athlete.volume', 'athlete.records', 'athlete.measurements', 'athlete.photos.*') ? 'active' : '' }}"
       aria-current="{{ request()->routeIs('athlete.volume', 'athlete.records', 'athlete.measurements', 'athlete.photos.*') ? 'page' : 'false' }}"
       aria-label="Progressi">
        <span class="nav-pill">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22
                         m0 0l-3.714-.857m3.714.857l-.857 3.714"/>
            </svg>
        </span>
        <span>Progressi</span>
    </a>

    {{-- Profilo (con badge messaggi non letti) --}}
    <a href="{{ route('athlete.profile') }}"
       class="{{ request()->routeIs('athlete.profile', 'athlete.messages', 'athlete.bookings', 'athlete.exercises*') ? 'active' : '' }}"
       aria-current="{{ request()->routeIs('athlete.profile', 'athlete.messages', 'athlete.bookings', 'athlete.exercises*') ? 'page' : 'false' }}"
       aria-label="Profilo"
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
                      d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
            </svg>
            <span x-show="unread > 0"
                  x-text="unread > 9 ? '9+' : unread"
                  class="nav-unread-badge"
                  aria-live="polite"></span>
        </span>
        <span>Profilo</span>
    </a>

</nav>
