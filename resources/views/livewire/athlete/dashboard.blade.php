<div>
    @if ($activeMesocycle === null)
        {{-- Nessun mesociclo attivo --}}
        <div class="home-empty">
            <svg class="home-empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                         M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="home-empty-title">Nessuna scheda attiva</p>
            <p class="home-empty-body">Il tuo trainer non ha ancora assegnato un mesociclo. Contattalo via messaggi.</p>
            <a href="{{ route('athlete.messages') }}" class="ig-btn ig-btn--secondary" style="margin-top:var(--ig-sp-4);">
                Apri messaggi
            </a>
        </div>

    @else

        {{-- ===== HERO SESSIONE ===== --}}
        @if ($nextSession !== null)
            @php
                $isInProgress = $nextSession->status === 'in_progress';
                $heroExercises = $nextSession->sessionExercises->take(3);
            @endphp

            <a href="{{ route('athlete.session', $nextSession) }}" class="home-hero">
                <div class="home-hero-header">
                    <span class="home-hero-badge {{ $isInProgress ? 'home-hero-badge--active' : '' }}">
                        {{ $isInProgress ? 'IN CORSO' : 'PROSSIMO' }}
                    </span>
                    @if ($nextSession->week?->is_deload)
                        <span class="home-hero-badge home-hero-badge--deload">DELOAD</span>
                    @endif
                </div>

                <h1 class="home-hero-name">{{ $nextSession->name }}</h1>

                @if ($heroExercises->isNotEmpty())
                    <div class="home-hero-exercises">
                        @foreach ($heroExercises as $se)
                            <span class="home-hero-ex-pill">{{ $se->exercise->name_it }}</span>
                        @endforeach
                        @if ($nextSession->sessionExercises->count() > 3)
                            <span class="home-hero-ex-pill home-hero-ex-pill--more">
                                +{{ $nextSession->sessionExercises->count() - 3 }}
                            </span>
                        @endif
                    </div>
                @endif

                <div class="home-hero-meta">
                    <span>
                        W{{ $nextSession->week?->week_number ?? '?' }}
                        di {{ $activeMesocycle->weeks_count }}
                    </span>
                    @if ($nextSession->scheduled_date)
                        <span>&bull; {{ $nextSession->scheduled_date->format('l d/m') }}</span>
                    @endif
                </div>

                <div class="home-hero-cta">
                    <span class="ig-btn ig-btn--primary ig-btn--lg" style="width:100%;">
                        {{ $isInProgress ? 'Riprendi sessione' : 'Inizia sessione' }}
                    </span>
                </div>
            </a>

        @else
            {{-- Tutte le sessioni completate o saltate --}}
            <div class="home-empty home-empty--success">
                <svg class="home-empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="home-empty-title">Settimana completata</p>
                <p class="home-empty-body">Tutte le sessioni di questa settimana sono concluse. Ottimo lavoro!</p>
            </div>
        @endif

        {{-- ===== STRIP MESOCICLO ===== --}}
        <div class="home-meso-strip">
            <div class="home-meso-meta">
                <span class="home-meso-name">{{ $activeMesocycle->name }}</span>
                <span class="home-meso-goal">{{ $this->goalLabel($activeMesocycle->goal) }}</span>
            </div>
            @if ($currentWeek !== null)
                <div class="home-meso-dots" aria-label="Settimane mesociclo">
                    @for ($w = 1; $w <= $activeMesocycle->weeks_count; $w++)
                        @php
                            $week = $activeMesocycle->weeks->firstWhere('week_number', $w);
                            $isCurrent = $week?->id === $currentWeek->id;
                            $isPast = $week !== null && ! $isCurrent && $week->week_number < $currentWeek->week_number;
                            $isDeloadW = $week?->is_deload;
                        @endphp
                        <span class="home-meso-dot
                            {{ $isCurrent ? 'home-meso-dot--current' : '' }}
                            {{ $isPast ? 'home-meso-dot--past' : '' }}
                            {{ $isDeloadW ? 'home-meso-dot--deload' : '' }}"
                              title="W{{ $w }}{{ $isDeloadW ? ' deload' : '' }}"
                              aria-label="Settimana {{ $w }}{{ $isDeloadW ? ' (deload)' : '' }}{{ $isCurrent ? ' — attuale' : '' }}">
                        </span>
                    @endfor
                </div>
                <span class="home-meso-week-label">
                    Settimana {{ $currentWeek->week_number }} di {{ $activeMesocycle->weeks_count }}
                    @if ($currentWeek->is_deload)
                        &bull; <strong>Deload</strong>
                    @endif
                </span>
            @endif
        </div>

        {{-- ===== ULTIMO ALLENAMENTO ===== --}}
        @if ($lastSession !== null)
            <div class="home-last">
                <p class="home-section-label">Ultimo allenamento</p>
                <div class="home-last-card">
                    <div class="home-last-info">
                        <span class="home-last-name">{{ $lastSession->name }}</span>
                        @if ($lastSession->completed_at)
                            <span class="home-last-date">{{ $lastSession->completed_at->format('d/m/Y') }}</span>
                        @endif
                    </div>
                    <div class="home-last-stats">
                        @if ($lastTonnage > 0)
                            <span class="home-last-stat">
                                <strong>{{ number_format($lastTonnage, 0, ',', '.') }}</strong>
                                <span>kg</span>
                            </span>
                        @endif
                        @if ($lastSetsCompleted > 0)
                            <span class="home-last-stat">
                                <strong>{{ $lastSetsCompleted }}</strong>
                                <span>set</span>
                            </span>
                        @endif
                    </div>
                    <a href="{{ route('athlete.session.recap', $lastSession) }}" class="home-last-link" aria-label="Vedi recap sessione">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        @endif

        {{-- ===== SESSIONI SETTIMANA (lista compatta) ===== --}}
        @if ($currentWeek !== null && $weekSessions->isNotEmpty())
            <p class="home-section-label">Questa settimana</p>

            <div class="home-week-list">
                @foreach ($weekSessions as $session)
                    @php $isClickable = in_array($session->status, ['planned', 'in_progress']); @endphp
                    <div class="home-week-item {{ $session->status === 'completed' ? 'home-week-item--done' : '' }}
                                               {{ $session->status === 'skipped' ? 'home-week-item--skipped' : '' }}">
                        <span class="home-week-dot home-week-dot--{{ $session->status }}"></span>
                        <span class="home-week-name">{{ $session->name }}</span>
                        <span class="home-week-date">
                            @if ($session->scheduled_date)
                                {{ $session->scheduled_date->format('d/m') }}
                            @endif
                        </span>
                        @if ($isClickable && $session->id !== $nextSession?->id)
                            <a href="{{ route('athlete.session', $session) }}" class="home-week-action" aria-label="Vai alla sessione">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        @elseif ($session->status === 'skipped')
                            <button wire:click="restoreSession({{ $session->id }})"
                                    wire:confirm="Ripristinare questa sessione come pianificata?"
                                    class="home-week-restore">
                                Ripristina
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

    @endif
</div>
