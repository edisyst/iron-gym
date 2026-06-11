<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Iron Gym — Allena il tuo potenziale</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,900&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            .hero-gradient {
                background: radial-gradient(ellipse at 60% 0%, rgba(220,38,38,0.15) 0%, transparent 60%),
                            radial-gradient(ellipse at 0% 80%, rgba(220,38,38,0.08) 0%, transparent 50%),
                            #0a0a0a;
            }
            .card-glow:hover {
                box-shadow: 0 0 0 1px rgba(220,38,38,0.4), 0 20px 40px rgba(220,38,38,0.08);
            }
            .stat-bar {
                background: linear-gradient(90deg, #dc2626, #991b1b);
                border-radius: 2px;
            }
            @keyframes fadeUp {
                from { opacity: 0; transform: translateY(24px); }
                to   { opacity: 1; transform: translateY(0); }
            }
            .fade-up { animation: fadeUp 0.6s ease both; }
            .delay-1 { animation-delay: 0.1s; }
            .delay-2 { animation-delay: 0.22s; }
            .delay-3 { animation-delay: 0.34s; }
            .delay-4 { animation-delay: 0.46s; }
        </style>
    </head>
    <body class="antialiased font-sans bg-[#0a0a0a] text-white">

        {{-- NAVBAR --}}
        <header class="fixed top-0 inset-x-0 z-50 border-b border-white/5 bg-[#0a0a0a]/80 backdrop-blur-md">
            <div class="mx-auto max-w-7xl px-6 flex items-center justify-between h-16">
                <div class="flex items-center gap-2.5">
                    <svg class="h-7 w-7 text-red-600" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20.57 14.86L22 13.43 20.57 12 17 15.57 8.43 7 12 3.43 10.57 2 9.14 3.43 7.71 2 5.57 4.14 4.14 2.71 2.71 4.14l1.43 1.43L2 7.71l1.43 1.43L2 10.57 3.43 12 7 8.43 15.57 17 12 20.57 13.43 22l1.43-1.43L16.29 22l2.14-2.14 1.43 1.43 1.43-1.43-1.43-1.43L22 16.29l-1.43-1.43z"/>
                    </svg>
                    <span class="text-lg font-bold tracking-tight">Iron<span class="text-red-600">Gym</span></span>
                </div>
                @if (Route::has('login'))
                    <nav class="flex items-center gap-2">
                        @auth
                            <a href="{{ url('/dashboard') }}"
                               class="rounded-lg px-4 py-2 text-sm font-medium bg-red-600 hover:bg-red-500 transition-colors">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="rounded-lg px-4 py-2 text-sm font-medium text-white/70 hover:text-white transition-colors">
                                Accedi
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                   class="rounded-lg px-4 py-2 text-sm font-medium bg-red-600 hover:bg-red-500 transition-colors">
                                    Inizia ora
                                </a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </div>
        </header>

        {{-- HERO --}}
        <section class="hero-gradient min-h-screen flex items-center pt-16">
            <div class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
                <div class="grid lg:grid-cols-2 gap-16 items-center">
                    <div>
                        <div class="fade-up inline-flex items-center gap-2 rounded-full border border-red-600/30 bg-red-600/10 px-4 py-1.5 text-xs font-semibold text-red-400 uppercase tracking-widest mb-8">
                            <span class="h-1.5 w-1.5 rounded-full bg-red-500 animate-pulse"></span>
                            Bodybuilding &amp; Fitness
                        </div>
                        <h1 class="fade-up delay-1 text-5xl lg:text-7xl font-black leading-none tracking-tight">
                            Allena il tuo<br>
                            <span class="text-red-600">potenziale.</span>
                        </h1>
                        <p class="fade-up delay-2 mt-6 text-lg text-white/50 max-w-md leading-relaxed">
                            Schede personalizzate, progressione scientifica e tracking in tempo reale.
                            Ogni set conta. Ogni rep ti avvicina all'obiettivo.
                        </p>
                        <div class="fade-up delay-3 mt-10 flex flex-wrap gap-4">
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                   class="inline-flex items-center gap-2 rounded-xl px-7 py-3.5 text-sm font-bold bg-red-600 hover:bg-red-500 transition-all hover:scale-[1.02] active:scale-[0.98]">
                                    Inizia gratis
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                </a>
                            @endif
                            @if (Route::has('login'))
                                <a href="{{ route('login') }}"
                                   class="inline-flex items-center gap-2 rounded-xl px-7 py-3.5 text-sm font-semibold text-white/70 border border-white/10 hover:border-white/25 hover:text-white transition-all">
                                    Ho già un account
                                </a>
                            @endif
                        </div>
                        {{-- stats --}}
                        <div class="fade-up delay-4 mt-14 grid grid-cols-3 gap-6">
                            <div>
                                <div class="text-3xl font-black text-white">83<span class="text-red-600">+</span></div>
                                <div class="mt-1 text-xs text-white/40 uppercase tracking-wider">Esercizi</div>
                            </div>
                            <div>
                                <div class="text-3xl font-black text-white">26<span class="text-red-600">+</span></div>
                                <div class="mt-1 text-xs text-white/40 uppercase tracking-wider">Muscoli tracciati</div>
                            </div>
                            <div>
                                <div class="text-3xl font-black text-white">∞</div>
                                <div class="mt-1 text-xs text-white/40 uppercase tracking-wider">Progressi</div>
                            </div>
                        </div>
                    </div>

                    {{-- feature card grid --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div class="card-glow col-span-2 rounded-2xl border border-white/8 bg-white/[0.03] p-6 transition-all duration-300">
                            <div class="flex items-start gap-4">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-600/15">
                                    <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-white">Progressione scientifica</h3>
                                    <p class="mt-1 text-sm text-white/45 leading-relaxed">Volume landmarks personalizzati (MEV → MAV → MRV) per ogni gruppo muscolare. Il sistema calcola la progressione ottimale settimana per settimana.</p>
                                </div>
                            </div>
                            <div class="mt-5 space-y-2">
                                <div class="flex items-center justify-between text-xs text-white/40">
                                    <span>Petto</span><span>14 / 20 set</span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-white/5">
                                    <div class="stat-bar h-1.5 rounded-full" style="width:70%"></div>
                                </div>
                                <div class="flex items-center justify-between text-xs text-white/40">
                                    <span>Schiena</span><span>16 / 22 set</span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-white/5">
                                    <div class="stat-bar h-1.5 rounded-full" style="width:73%"></div>
                                </div>
                                <div class="flex items-center justify-between text-xs text-white/40">
                                    <span>Gambe</span><span>18 / 24 set</span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-white/5">
                                    <div class="stat-bar h-1.5 rounded-full" style="width:75%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="card-glow rounded-2xl border border-white/8 bg-white/[0.03] p-5 transition-all duration-300">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-600/15">
                                <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <h3 class="mt-3 font-bold text-white text-sm">Tecniche avanzate</h3>
                            <p class="mt-1 text-xs text-white/45 leading-relaxed">Drop set, rest-pause, myo-reps, cluster. Ogni tecnica di intensificazione supportata.</p>
                        </div>

                        <div class="card-glow rounded-2xl border border-white/8 bg-white/[0.03] p-5 transition-all duration-300">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-600/15">
                                <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <h3 class="mt-3 font-bold text-white text-sm">Mesocicli strutturati</h3>
                            <p class="mt-1 text-xs text-white/45 leading-relaxed">Periodizzazione lineare, DUP o a blocchi. Deload automatico a fine ciclo.</p>
                        </div>

                        <div class="card-glow col-span-2 rounded-2xl border border-white/8 bg-white/[0.03] p-5 transition-all duration-300">
                            <div class="flex items-center gap-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-600/15">
                                    <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-white text-sm">Trainer + Atleta</h3>
                                    <p class="mt-0.5 text-xs text-white/45">Il trainer progetta, l'atleta esegue. Feedback post-sessione, RPE, autoregolazione integrata.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- FEATURE STRIP --}}
        <section class="border-y border-white/5 bg-white/[0.02]">
            <div class="mx-auto max-w-7xl px-6 py-16">
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-600/15">
                            <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-white text-sm">Schede personalizzate</h4>
                            <p class="mt-1 text-xs text-white/40 leading-relaxed">Template riutilizzabili, istanziati e snapshottati per ogni atleta.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-600/15">
                            <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-white text-sm">Tracking progressi</h4>
                            <p class="mt-1 text-xs text-white/40 leading-relaxed">e1RM calcolato con formula Epley, storico per esercizio e per muscolo.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-600/15">
                            <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-white text-sm">Feedback integrato</h4>
                            <p class="mt-1 text-xs text-white/40 leading-relaxed">Pump, soreness, joint pain post-sessione. Autoregolazione basata sui dati.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-600/15">
                            <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-white text-sm">App atleta</h4>
                            <p class="mt-1 text-xs text-white/40 leading-relaxed">PWA ottimizzata per in-gym. Log veloce, timer di recupero, storico set.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- CTA FINALE --}}
        <section class="py-28">
            <div class="mx-auto max-w-3xl px-6 text-center">
                <h2 class="text-4xl lg:text-5xl font-black tracking-tight">
                    Pronto a <span class="text-red-600">spingere oltre</span><br>i tuoi limiti?
                </h2>
                <p class="mt-5 text-base text-white/45 max-w-lg mx-auto leading-relaxed">
                    Unisciti agli atleti che usano Iron Gym per programmare con precisione e raggiungere i loro obiettivi di ipertrofia.
                </p>
                <div class="mt-10 flex flex-wrap gap-4 justify-center">
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center gap-2 rounded-xl px-8 py-4 text-sm font-bold bg-red-600 hover:bg-red-500 transition-all hover:scale-[1.02]">
                            Crea il tuo account — gratis
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </a>
                    @endif
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}"
                           class="inline-flex items-center gap-2 rounded-xl px-8 py-4 text-sm font-semibold text-white/60 border border-white/10 hover:border-white/20 hover:text-white transition-all">
                            Accedi
                        </a>
                    @endif
                </div>
            </div>
        </section>

        <footer class="border-t border-white/5 py-8">
            <div class="mx-auto max-w-7xl px-6 flex items-center justify-between text-xs text-white/20">
                <span>Iron<span class="text-red-800">Gym</span></span>
                <span>PHP v{{ PHP_VERSION }}</span>
            </div>
        </footer>

    </body>
</html>
