<div>
    @if ($activeMesocycle === null)
        {{-- Nessuna scheda assegnata --}}
        <div class="athlete-card" style="text-align: center; padding: 40px 16px;">
            <svg style="width:56px;height:56px;color:#444;margin:0 auto 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                         M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p style="color:#888;font-size:15px;line-height:1.6;">
                Nessuna scheda assegnata.<br>
                Contatta il tuo trainer.
            </p>
        </div>
    @else
        {{-- Header mesociclo --}}
        <div class="athlete-card">
            <p class="section-title">Mesociclo attivo</p>
            <h1 style="font-size:20px;font-weight:700;margin-bottom:8px;">
                {{ $activeMesocycle->name }}
            </h1>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <span class="athlete-badge badge-gray">
                    {{ $this->goalLabel($activeMesocycle->goal) }}
                </span>
                @if ($currentWeek !== null)
                    <span class="athlete-badge badge-gray">
                        Settimana {{ $currentWeek->week_number }}
                        di {{ $activeMesocycle->weeks_count }}
                    </span>
                    @if ($currentWeek->is_deload)
                        <span class="athlete-badge badge-accent">DELOAD</span>
                    @endif
                @endif
            </div>
        </div>

        {{-- Sessioni della settimana corrente --}}
        @if ($currentWeek !== null)
            <p class="section-title" style="padding: 0 4px;">Sessioni di questa settimana</p>

            @forelse ($weekSessions as $session)
                @php
                    $isClickable = in_array($session->status, ['planned', 'in_progress']);
                @endphp

                @if ($isClickable)
                    <a href="{{ route('athlete.session', $session) }}"
                       style="display:block;text-decoration:none;color:inherit;">
                @else
                    <div>
                @endif

                <div class="athlete-card" style="display:flex;align-items:center;gap:12px;
                     {{ $isClickable ? 'cursor:pointer;' : 'opacity:.6;' }}">
                    {{-- Icona status --}}
                    <div class="{{ $this->sessionStatusClass($session->status) }}" style="flex-shrink:0;">
                        @if ($session->status === 'completed')
                            <svg style="width:28px;height:28px;" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        @elseif ($session->status === 'in_progress')
                            <svg style="width:28px;height:28px;" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                            </svg>
                        @elseif ($session->status === 'skipped')
                            <svg style="width:28px;height:28px;" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            <svg style="width:28px;height:28px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <circle cx="12" cy="12" r="9" stroke-dasharray="4 2"/>
                            </svg>
                        @endif
                    </div>

                    <div style="flex:1;min-width:0;">
                        <p style="font-size:16px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ $session->name }}
                        </p>
                        <p style="font-size:13px;color:#888;margin-top:2px;">
                            {{ $this->sessionStatusLabel($session->status) }}
                            @if ($session->scheduled_date)
                                &bull; {{ $session->scheduled_date->format('d/m') }}
                            @endif
                        </p>
                    </div>

                    @if ($isClickable)
                        <svg style="width:20px;height:20px;color:#555;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    @elseif ($session->status === 'skipped')
                        <button wire:click.stop="restoreSession({{ $session->id }})"
                                wire:confirm="Ripristinare questa sessione come pianificata?"
                                style="flex-shrink:0;background:#1E1E1E;border:1px solid #444;color:#ccc;
                                       font-size:12px;font-weight:600;padding:6px 12px;border-radius:8px;cursor:pointer;">
                            Ripristina
                        </button>
                    @endif
                </div>

                @if ($isClickable)
                    </a>
                @else
                    </div>
                @endif
            @empty
                <div class="athlete-card">
                    <p style="color:#888;text-align:center;">Nessuna sessione in questa settimana.</p>
                </div>
            @endforelse
        @endif
    @endif
</div>
