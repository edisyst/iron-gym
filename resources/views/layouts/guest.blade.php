<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Iron Gym') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased font-sans bg-[#0a0a0a] text-white min-h-screen">

    <div class="min-h-screen flex">

        {{-- PANNELLO SINISTRO --}}
        <div class="hidden lg:flex lg:w-[45%] flex-col justify-between p-12 border-r border-white/5"
             style="background: radial-gradient(ellipse at 80% 20%, rgba(220,38,38,0.18) 0%, transparent 55%), #0a0a0a;">

            <a href="/" wire:navigate class="flex items-center gap-2.5">
                <svg class="h-8 w-8 text-red-600" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20.57 14.86L22 13.43 20.57 12 17 15.57 8.43 7 12 3.43 10.57 2 9.14 3.43 7.71 2 5.57 4.14 4.14 2.71 2.71 4.14l1.43 1.43L2 7.71l1.43 1.43L2 10.57 3.43 12 7 8.43 15.57 17 12 20.57 13.43 22l1.43-1.43L16.29 22l2.14-2.14 1.43 1.43 1.43-1.43-1.43-1.43L22 16.29l-1.43-1.43z"/>
                </svg>
                <span class="text-xl font-black tracking-tight">Iron<span class="text-red-600">Gym</span></span>
            </a>

            <div>
                <div class="inline-flex items-center gap-2 rounded-full border border-red-600/30 bg-red-600/10 px-4 py-1.5 text-xs font-semibold text-red-400 uppercase tracking-widest mb-7">
                    <span class="h-1.5 w-1.5 rounded-full bg-red-500 animate-pulse"></span>
                    Bodybuilding &amp; Fitness
                </div>
                <h2 class="text-4xl xl:text-5xl font-black leading-none tracking-tight">
                    Allena il tuo<br>
                    <span class="text-red-600">potenziale.</span>
                </h2>
                <p class="mt-5 text-white/45 leading-relaxed max-w-sm text-sm">
                    Progressione scientifica, mesocicli strutturati e tracking in tempo reale.
                    Ogni set ti avvicina all'obiettivo.
                </p>

                <div class="mt-10 grid grid-cols-3 gap-6">
                    <div>
                        <div class="text-3xl font-black">83<span class="text-red-600">+</span></div>
                        <div class="mt-1 text-xs text-white/35 uppercase tracking-wider">Esercizi</div>
                    </div>
                    <div>
                        <div class="text-3xl font-black">26<span class="text-red-600">+</span></div>
                        <div class="mt-1 text-xs text-white/35 uppercase tracking-wider">Muscoli</div>
                    </div>
                    <div>
                        <div class="text-2xl font-black">MEV<span class="text-red-600">→</span></div>
                        <div class="mt-1 text-xs text-white/35 uppercase tracking-wider">MRV</div>
                    </div>
                </div>
            </div>

            <div class="text-xs text-white/15">
                &copy; {{ date('Y') }} IronGym
            </div>
        </div>

        {{-- PANNELLO DESTRO (form) --}}
        <div class="flex-1 flex flex-col items-center justify-center px-6 py-12 lg:px-16"
             style="background: #0d0d0d;">

            {{-- Logo mobile --}}
            <div class="lg:hidden mb-8 flex items-center gap-2.5">
                <svg class="h-7 w-7 text-red-600" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20.57 14.86L22 13.43 20.57 12 17 15.57 8.43 7 12 3.43 10.57 2 9.14 3.43 7.71 2 5.57 4.14 4.14 2.71 2.71 4.14l1.43 1.43L2 7.71l1.43 1.43L2 10.57 3.43 12 7 8.43 15.57 17 12 20.57 13.43 22l1.43-1.43L16.29 22l2.14-2.14 1.43 1.43 1.43-1.43-1.43-1.43L22 16.29l-1.43-1.43z"/>
                </svg>
                <span class="text-lg font-black tracking-tight">Iron<span class="text-red-600">Gym</span></span>
            </div>

            <div class="w-full max-w-md">
                <div class="rounded-2xl border border-white/8 bg-white/[0.03] px-8 py-9">
                    {{ $slot }}
                </div>
            </div>
        </div>

    </div>

</body>
</html>
