<div>
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/session-recap.css') }}">
    @endpush

    <div class="recap-page">

        {{-- Card esportabile --}}
        <div id="recap-card">

            {{-- Header arancio brand --}}
            <div class="rc-header">
                <div>
                    <div class="rc-brand">Iron Gym</div>
                    <div style="font-size:11px;color:rgba(255,255,255,.75);margin-top:2px;">Riepilogo sessione</div>
                </div>
                <div class="rc-date">
                    {{ $session->completed_at?->format('d/m/Y') }}<br>
                    {{ $session->completed_at?->format('H:i') }}
                </div>
            </div>

            {{-- Nome sessione --}}
            <div class="rc-title">{{ $session->name }}</div>

            {{-- Metriche --}}
            <div class="rc-stats">
                <div class="rc-stat">
                    <div class="rc-stat-value">
                        @if ($recap['duration_minutes'] !== null)
                            {{ $recap['duration_minutes'] }}
                        @else
                            —
                        @endif
                    </div>
                    <div class="rc-stat-label">Minuti</div>
                </div>
                <div class="rc-stat">
                    <div class="rc-stat-value">
                        @if ($recap['tonnage_kg'] > 0)
                            {{ number_format($recap['tonnage_kg'] / 1000, 1) }}t
                        @else
                            —
                        @endif
                    </div>
                    <div class="rc-stat-label">Tonnellaggio</div>
                </div>
                <div class="rc-stat">
                    <div class="rc-stat-value">
                        {{ $recap['sets_completed'] }}<span style="font-size:14px;color:#444;">/{{ $recap['sets_prescribed'] }}</span>
                    </div>
                    <div class="rc-stat-label">Set</div>
                </div>
            </div>

            {{-- PR ottenuti --}}
            @if (count($recap['prs']) > 0)
                <div class="rc-prs">
                    <div class="rc-section-title">Nuovo record personale</div>
                    <div>
                        @foreach ($recap['prs'] as $pr)
                            <span class="rc-pr-badge">
                                <svg class="rc-pr-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                </svg>
                                {{ $pr['exercise_name'] }} — {{ $pr['value'] }} kg e1RM
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Top 3 muscoli --}}
            @if (count($recap['top_muscles']) > 0)
                <div class="rc-muscles">
                    <div class="rc-section-title">Muscoli più allenati</div>
                    @php $maxScore = $recap['top_muscles'][0]['score'] ?? 1; @endphp
                    @foreach ($recap['top_muscles'] as $i => $muscle)
                        <div class="rc-muscle-row">
                            <div class="rc-muscle-rank">{{ $i + 1 }}</div>
                            <div class="rc-muscle-name">{{ $muscle['name_it'] }}</div>
                            <div class="rc-muscle-bar-wrap">
                                <div class="rc-muscle-bar"
                                     style="width:{{ $maxScore > 0 ? round($muscle['score'] / $maxScore * 100) : 0 }}%">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Footer brand --}}
            <div class="rc-footer">
                <svg class="rc-footer-logo" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20.57 14.86L22 13.43 20.57 12 17 15.57 8.43 7 12 3.43 10.57 2 9.14 3.43 7.71 2 5.57 4.14 4.14 2.71 2.71 4.14l1.43 1.43L2 7.71l1.43 1.43L2 10.57 3.43 12 7 8.43 15.57 17 12 20.57 13.43 22l1.43-1.43L16.29 22l2.14-2.14 1.43 1.43 1.43-1.43-1.43-1.43L22 16.29l-1.43-1.43z"/>
                </svg>
                <span class="rc-footer-text">allenato con Iron Gym</span>
            </div>
        </div>

        {{-- Azioni fuori dalla card (non esportate nell'immagine) --}}
        <div class="recap-actions">
            <button id="recap-share-btn"
                    class="recap-btn-share"
                    onclick="window.exportRecapCard()">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Condividi
            </button>
            <a href="{{ route('athlete.dashboard') }}" class="recap-btn-close">
                Chiudi
            </a>
        </div>

        <p class="recap-hint">
            Tocca "Condividi" per salvare l'immagine o condividerla.
        </p>
    </div>

    @push('scripts')
        @vite(['resources/js/session-recap.js'])
    @endpush
</div>
