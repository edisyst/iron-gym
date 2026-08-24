<div>
    <h2 style="font-size:20px;font-weight:700;margin-bottom:4px;">Profilo</h2>
    <p style="font-size:13px;color:var(--ig-text-3);margin-bottom:20px;">{{ auth()->user()->email }}</p>

    {{-- Tab sezioni --}}
    <div class="ig-tab-group">
        <button type="button" wire:click="$set('activeSection','info')"
                class="ig-tab {{ $activeSection === 'info' ? 'ig-tab--active' : '' }}">
            Dati
        </button>
        <button type="button" wire:click="$set('activeSection','abbonamento')"
                class="ig-tab {{ $activeSection === 'abbonamento' ? 'ig-tab--active' : '' }}">
            Abbonamento
        </button>
        <button type="button" wire:click="$set('activeSection','pt')"
                class="ig-tab {{ $activeSection === 'pt' ? 'ig-tab--active' : '' }}">
            Sessioni PT
        </button>
        <button type="button" wire:click="$set('activeSection','misurazioni')"
                class="ig-tab {{ $activeSection === 'misurazioni' ? 'ig-tab--active' : '' }}">
            Misurazioni
        </button>
        <button type="button" wire:click="$set('activeSection','record')"
                class="ig-tab {{ $activeSection === 'record' ? 'ig-tab--active' : '' }}">
            Record
        </button>
        <button type="button" wire:click="$set('activeSection','sessioni')"
                class="ig-tab {{ $activeSection === 'sessioni' ? 'ig-tab--active' : '' }}">
            Sessioni
        </button>
        @if ($groupClassesEnabled)
        <button type="button" wire:click="$set('activeSection','corsi')"
                class="ig-tab {{ $activeSection === 'corsi' ? 'ig-tab--active' : '' }}">
            Corsi
        </button>
        @endif
        <button type="button" wire:click="$set('activeSection','accessi')"
                class="ig-tab {{ $activeSection === 'accessi' ? 'ig-tab--active' : '' }}">
            Accessi
        </button>
        <button type="button" wire:click="$set('activeSection','messaggi')"
                class="ig-tab {{ $activeSection === 'messaggi' ? 'ig-tab--active' : '' }}">
            Messaggi
            @if ($unreadMessagesCount > 0)
                <span style="display:inline-flex;align-items:center;justify-content:center;
                             background:var(--ig-danger);color:#fff;font-size:10px;font-weight:700;
                             border-radius:var(--ig-radius-full);min-width:16px;height:16px;
                             padding:0 4px;margin-left:4px;">
                    {{ $unreadMessagesCount }}
                </span>
            @endif
        </button>
        <button type="button" wire:click="$set('activeSection','password')"
                class="ig-tab {{ $activeSection === 'password' ? 'ig-tab--active' : '' }}">
            Password
        </button>
        <button type="button" wire:click="$set('activeSection','danger')"
                class="ig-tab ig-tab--danger {{ $activeSection === 'danger' ? 'ig-tab--active' : '' }}">
            Account
        </button>
    </div>

    {{-- ===== SEZIONE DATI ===== --}}
    @if ($activeSection === 'info')
        <form wire:submit="updateProfile">
            <div class="athlete-card">
                <div class="section-title" style="margin-bottom:16px;">INFORMAZIONI PERSONALI</div>

                @if ($profileMessage)
                    <div style="background:var(--ig-success-subtle);border:1px solid rgba(34,197,94,.3);border-radius:8px;
                                padding:10px 14px;margin-bottom:16px;font-size:14px;color:var(--ig-success);">
                        {{ $profileMessage }}
                    </div>
                @endif

                <div style="margin-bottom:16px;">
                    <label class="ig-form-label">Nome</label>
                    <input type="text" wire:model="name" autocomplete="name"
                           class="ig-form-input {{ $errors->has('name') ? 'is-invalid' : '' }}">
                    @error('name') <span class="ig-field-error">{{ $message }}</span> @enderror
                </div>

                <div style="margin-bottom:20px;">
                    <label class="ig-form-label">Email</label>
                    <input type="email" wire:model="email" autocomplete="email"
                           class="ig-form-input {{ $errors->has('email') ? 'is-invalid' : '' }}">
                    @error('email') <span class="ig-field-error">{{ $message }}</span> @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled"
                        style="width:100%;background:var(--ig-accent);color:#fff;border:none;border-radius:8px;
                               padding:13px;font-size:15px;font-weight:600;cursor:pointer;">
                    <span wire:loading.remove wire:target="updateProfile">Salva modifiche</span>
                    <span wire:loading wire:target="updateProfile">Salvataggio...</span>
                </button>
            </div>
        </form>

        {{-- Info ruolo --}}
        <div class="athlete-card">
            <div class="section-title" style="margin-bottom:12px;">RUOLO</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                @foreach (auth()->user()->getRoleNames() as $role)
                    <span class="athlete-badge badge-gray" style="text-transform:capitalize;">{{ $role }}</span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ===== SEZIONE ABBONAMENTO ===== --}}
    @if ($activeSection === 'abbonamento')
        <div class="athlete-card">
            <div class="section-title" style="margin-bottom:16px;">ABBONAMENTO ATTIVO</div>
            @if ($subscription)
                @php
                    $daysLeft = (int) now()->startOfDay()->diffInDays($subscription->expires_at, false);
                    $isExpiringSoon = $daysLeft >= 0 && $daysLeft <= 14;
                    $isExpired = $daysLeft < 0;
                    $statusColor = $isExpired ? 'var(--ig-danger)' : ($isExpiringSoon ? 'var(--ig-warning)' : 'var(--ig-success)');
                    $statusLabel = $isExpired ? 'Scaduto' : ($isExpiringSoon ? 'In scadenza' : 'Attivo');
                @endphp

                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                    <span style="font-size:var(--ig-text-md);font-weight:700;color:var(--ig-text-1);">
                        {{ $subscription->plan?->name ?? 'Piano sconosciuto' }}
                    </span>
                    <span style="font-size:var(--ig-text-xs);font-weight:700;color:{{ $statusColor }};
                                 background:color-mix(in srgb, {{ $statusColor }} 15%, transparent);
                                 padding:3px 10px;border-radius:var(--ig-radius-full);">
                        {{ $statusLabel }}
                    </span>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div style="background:var(--ig-surface-raised);border-radius:var(--ig-radius-sm);padding:12px;">
                        <div style="font-size:var(--ig-text-xs);color:var(--ig-text-3);margin-bottom:4px;">INIZIO</div>
                        <div style="font-size:var(--ig-text-sm);font-weight:600;color:var(--ig-text-1);">
                            {{ $subscription->started_at->format('d/m/Y') }}
                        </div>
                    </div>
                    <div style="background:var(--ig-surface-raised);border-radius:var(--ig-radius-sm);padding:12px;">
                        <div style="font-size:var(--ig-text-xs);color:var(--ig-text-3);margin-bottom:4px;">SCADENZA</div>
                        <div style="font-size:var(--ig-text-sm);font-weight:600;color:{{ $statusColor }};">
                            {{ $subscription->expires_at->format('d/m/Y') }}
                        </div>
                    </div>
                </div>

                @if (! $isExpired)
                    <div style="font-size:var(--ig-text-sm);color:var(--ig-text-2);">
                        @if ($daysLeft === 0)
                            Scade oggi.
                        @else
                            {{ $daysLeft }} {{ $daysLeft === 1 ? 'giorno rimanente' : 'giorni rimanenti' }}.
                        @endif
                    </div>
                @endif

                @if ($subscription->accesses_remaining !== null)
                    <div style="margin-top:12px;font-size:var(--ig-text-sm);color:var(--ig-text-2);">
                        Accessi rimanenti: <strong style="color:var(--ig-text-1);">{{ $subscription->accesses_remaining }}</strong>
                    </div>
                @endif
            @else
                <div style="text-align:center;padding:var(--ig-sp-6) 0;color:var(--ig-text-3);">
                    <p style="font-size:var(--ig-text-base);margin:0;">Nessun abbonamento attivo.</p>
                    <p style="font-size:var(--ig-text-sm);margin:8px 0 0;">Rivolgiti alla reception per rinnovarlo.</p>
                </div>
            @endif
        </div>
    @endif

    {{-- ===== SEZIONE SESSIONI PT ===== --}}
    @if ($activeSection === 'pt')
        @php
            $statusLabel = fn(string $s) => match($s) {
                'pending'   => 'In attesa',
                'confirmed' => 'Confermata',
                'completed' => 'Completata',
                'cancelled' => 'Annullata',
                'no_show'   => 'Assente',
                default     => $s,
            };
            $statusColor = fn(string $s) => match($s) {
                'confirmed' => 'var(--ig-success)',
                'pending'   => 'var(--ig-warning)',
                'completed' => 'var(--ig-accent)',
                default     => 'var(--ig-text-3)',
            };
        @endphp

        <div class="athlete-card">
            <div class="section-title" style="margin-bottom:16px;">PROSSIME SESSIONI PT</div>
            @forelse ($upcomingPtBookings as $booking)
                <div style="display:flex;align-items:center;justify-content:space-between;
                            padding:12px 0;border-bottom:1px solid var(--ig-border);">
                    <div>
                        <div style="font-size:var(--ig-text-sm);font-weight:600;color:var(--ig-text-1);">
                            {{ $booking->trainer->name ?? '—' }}
                        </div>
                        <div style="font-size:var(--ig-text-xs);color:var(--ig-text-3);margin-top:2px;">
                            {{ \Carbon\Carbon::parse($booking->booked_date)->format('d/m/Y') }}
                            &middot;
                            {{ \Carbon\Carbon::parse($booking->start_time)->format('H:i') }}
                        </div>
                    </div>
                    <span style="font-size:var(--ig-text-xs);font-weight:700;
                                 color:{{ $statusColor($booking->status) }};
                                 background:color-mix(in srgb, {{ $statusColor($booking->status) }} 15%, transparent);
                                 padding:3px 10px;border-radius:var(--ig-radius-full);">
                        {{ $statusLabel($booking->status) }}
                    </span>
                </div>
            @empty
                <p style="font-size:var(--ig-text-sm);color:var(--ig-text-3);margin:0;">
                    Nessuna sessione PT in programma.
                </p>
            @endforelse
        </div>

        @if ($pastPtBookings->isNotEmpty())
            <div class="athlete-card">
                <div class="section-title" style="margin-bottom:16px;">STORICO PT</div>
                @foreach ($pastPtBookings as $booking)
                    <div style="display:flex;align-items:center;justify-content:space-between;
                                padding:12px 0;border-bottom:1px solid var(--ig-border);">
                        <div>
                            <div style="font-size:var(--ig-text-sm);font-weight:600;color:var(--ig-text-1);">
                                {{ $booking->trainer->name ?? '—' }}
                            </div>
                            <div style="font-size:var(--ig-text-xs);color:var(--ig-text-3);margin-top:2px;">
                                {{ \Carbon\Carbon::parse($booking->booked_date)->format('d/m/Y') }}
                                &middot;
                                {{ \Carbon\Carbon::parse($booking->start_time)->format('H:i') }}
                            </div>
                        </div>
                        <span style="font-size:var(--ig-text-xs);font-weight:700;
                                     color:{{ $statusColor($booking->status) }};
                                     background:color-mix(in srgb, {{ $statusColor($booking->status) }} 15%, transparent);
                                     padding:3px 10px;border-radius:var(--ig-radius-full);">
                            {{ $statusLabel($booking->status) }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    @endif

    {{-- ===== SEZIONE MISURAZIONI ===== --}}
    @if ($activeSection === 'misurazioni')
        <div class="athlete-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <div class="section-title">MISURAZIONI CORPOREE</div>
                <a href="{{ route('athlete.measurements') }}"
                   style="font-size:var(--ig-text-xs);font-weight:600;color:var(--ig-accent);text-decoration:none;">
                    + Aggiungi
                </a>
            </div>

            @forelse ($recentMeasurements as $m)
                <div style="padding:12px 0;border-bottom:1px solid var(--ig-border);">
                    <div style="font-size:var(--ig-text-xs);color:var(--ig-text-3);margin-bottom:6px;">
                        {{ $m->measured_at->format('d/m/Y') }}
                    </div>
                    <div style="display:flex;gap:16px;flex-wrap:wrap;">
                        @if ($m->weight_kg !== null)
                            <span style="font-size:var(--ig-text-sm);color:var(--ig-text-1);">
                                <span style="color:var(--ig-text-3);font-size:var(--ig-text-xs);">PESO</span>
                                <strong>{{ $m->weight_kg }} kg</strong>
                            </span>
                        @endif
                        @if ($m->body_fat_pct !== null)
                            <span style="font-size:var(--ig-text-sm);color:var(--ig-text-1);">
                                <span style="color:var(--ig-text-3);font-size:var(--ig-text-xs);">BF%</span>
                                <strong>{{ $m->body_fat_pct }}%</strong>
                            </span>
                        @endif
                        @if ($m->waist_cm !== null)
                            <span style="font-size:var(--ig-text-sm);color:var(--ig-text-1);">
                                <span style="color:var(--ig-text-3);font-size:var(--ig-text-xs);">VITA</span>
                                <strong>{{ $m->waist_cm }} cm</strong>
                            </span>
                        @endif
                        @if ($m->chest_cm !== null)
                            <span style="font-size:var(--ig-text-sm);color:var(--ig-text-1);">
                                <span style="color:var(--ig-text-3);font-size:var(--ig-text-xs);">PETTO</span>
                                <strong>{{ $m->chest_cm }} cm</strong>
                            </span>
                        @endif
                    </div>
                    @if ($m->notes)
                        <div style="font-size:var(--ig-text-xs);color:var(--ig-text-3);margin-top:4px;">
                            {{ $m->notes }}
                        </div>
                    @endif
                </div>
            @empty
                <div style="text-align:center;padding:var(--ig-sp-6) 0;color:var(--ig-text-3);">
                    <p style="font-size:var(--ig-text-base);margin:0;">Nessuna misurazione registrata.</p>
                    <a href="{{ route('athlete.measurements') }}"
                       style="font-size:var(--ig-text-sm);color:var(--ig-accent);text-decoration:none;
                              display:inline-block;margin-top:8px;">
                        Registra la prima misurazione →
                    </a>
                </div>
            @endforelse

            @if ($recentMeasurements->isNotEmpty())
                <div style="text-align:center;margin-top:14px;">
                    <a href="{{ route('athlete.measurements') }}"
                       style="font-size:var(--ig-text-sm);color:var(--ig-accent);text-decoration:none;">
                        Vedi tutte e aggiungi →
                    </a>
                </div>
            @endif
        </div>
    @endif

    {{-- ===== SEZIONE RECORD ===== --}}
    @if ($activeSection === 'record')
        <div class="athlete-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <div class="section-title">PERSONAL RECORD (e1RM)</div>
                <a href="{{ route('athlete.records') }}"
                   style="font-size:var(--ig-text-xs);font-weight:600;color:var(--ig-accent);text-decoration:none;">
                    Vedi tutti
                </a>
            </div>

            @forelse ($recentPrs as $pr)
                <div style="display:flex;align-items:center;justify-content:space-between;
                            padding:12px 0;border-bottom:1px solid var(--ig-border);">
                    <div>
                        <div style="font-size:var(--ig-text-sm);font-weight:600;color:var(--ig-text-1);">
                            {{ $pr->exercise?->name_it ?? '—' }}
                        </div>
                        <div style="font-size:var(--ig-text-xs);color:var(--ig-text-3);margin-top:2px;">
                            {{ $pr->achieved_at->format('d/m/Y') }}
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <span style="font-size:var(--ig-text-md);font-weight:700;color:var(--ig-accent);">
                            {{ number_format((float) $pr->value, 1) }}
                        </span>
                        <span style="font-size:var(--ig-text-xs);color:var(--ig-text-3);margin-left:2px;">kg</span>
                    </div>
                </div>
            @empty
                <div style="text-align:center;padding:var(--ig-sp-6) 0;color:var(--ig-text-3);">
                    <p style="font-size:var(--ig-text-base);margin:0;">Nessun record registrato.</p>
                    <p style="font-size:var(--ig-text-sm);margin:8px 0 0;">I PR vengono rilevati automaticamente durante le sessioni.</p>
                </div>
            @endforelse

            @if ($recentPrs->isNotEmpty())
                <div style="text-align:center;margin-top:14px;">
                    <a href="{{ route('athlete.records') }}"
                       style="font-size:var(--ig-text-sm);color:var(--ig-accent);text-decoration:none;">
                        Vedi tutti i record →
                    </a>
                </div>
            @endif
        </div>
    @endif

    {{-- ===== SEZIONE SESSIONI ===== --}}
    @if ($activeSection === 'sessioni')
        <div class="athlete-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <div class="section-title">SESSIONI RECENTI</div>
                <a href="{{ route('athlete.history') }}"
                   style="font-size:var(--ig-text-xs);font-weight:600;color:var(--ig-accent);text-decoration:none;">
                    Vedi storico
                </a>
            </div>

            @forelse ($recentSessions as $session)
                @php
                    $isSkipped = $session->status === 'skipped';
                    $sessionDate = $session->completed_at ?? $session->scheduled_date;
                    $duration = null;
                    if ($session->started_at && $session->completed_at) {
                        $minutes = (int) $session->started_at->diffInMinutes($session->completed_at);
                        $duration = $minutes > 0 ? "{$minutes} min" : null;
                    }
                @endphp
                <div style="display:flex;align-items:center;justify-content:space-between;
                            padding:12px 0;border-bottom:1px solid var(--ig-border);">
                    <div>
                        <div style="font-size:var(--ig-text-sm);font-weight:600;
                                    color:{{ $isSkipped ? 'var(--ig-text-3)' : 'var(--ig-text-1)' }};">
                            {{ $session->name ?? 'Sessione' }}
                        </div>
                        <div style="font-size:var(--ig-text-xs);color:var(--ig-text-3);margin-top:2px;">
                            {{ $sessionDate?->format('d/m/Y') }}
                            @if ($duration)
                                &middot; {{ $duration }}
                            @endif
                        </div>
                    </div>
                    <span style="font-size:var(--ig-text-xs);font-weight:700;
                                 color:{{ $isSkipped ? 'var(--ig-text-3)' : 'var(--ig-success)' }};
                                 background:{{ $isSkipped ? 'var(--ig-surface-raised)' : 'color-mix(in srgb, var(--ig-success) 15%, transparent)' }};
                                 padding:3px 10px;border-radius:var(--ig-radius-full);">
                        {{ $isSkipped ? 'Saltata' : 'Completata' }}
                    </span>
                </div>
            @empty
                <div style="text-align:center;padding:var(--ig-sp-6) 0;color:var(--ig-text-3);">
                    <p style="font-size:var(--ig-text-base);margin:0;">Nessuna sessione completata.</p>
                </div>
            @endforelse

            @if ($recentSessions->isNotEmpty())
                <div style="text-align:center;margin-top:14px;">
                    <a href="{{ route('athlete.history') }}"
                       style="font-size:var(--ig-text-sm);color:var(--ig-accent);text-decoration:none;">
                        Storico completo →
                    </a>
                </div>
            @endif
        </div>
    @endif

    {{-- ===== SEZIONE CORSI COLLETTIVI ===== --}}
    @if ($activeSection === 'corsi' && $groupClassesEnabled)
        <div class="athlete-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <div class="section-title">PROSSIMI CORSI</div>
                <a href="{{ route('athlete.bookings') }}"
                   style="font-size:var(--ig-text-xs);font-weight:600;color:var(--ig-accent);text-decoration:none;">
                    Prenota
                </a>
            </div>

            @forelse ($upcomingClassBookings as $booking)
                @php
                    $occ = $booking->occurrence;
                    $course = $occ?->groupClass;
                    $isWaitlisted = $booking->status === 'waitlisted';
                @endphp
                <div style="display:flex;align-items:center;justify-content:space-between;
                            padding:12px 0;border-bottom:1px solid var(--ig-border);">
                    <div>
                        <div style="font-size:var(--ig-text-sm);font-weight:600;color:var(--ig-text-1);">
                            {{ $course?->name ?? '—' }}
                        </div>
                        <div style="font-size:var(--ig-text-xs);color:var(--ig-text-3);margin-top:2px;">
                            {{ \Carbon\Carbon::parse($occ?->date)->format('d/m/Y') }}
                            &middot;
                            {{ \Carbon\Carbon::parse($occ?->start_time)->format('H:i') }}
                        </div>
                    </div>
                    <span style="font-size:var(--ig-text-xs);font-weight:700;
                                 color:{{ $isWaitlisted ? 'var(--ig-warning)' : 'var(--ig-success)' }};
                                 background:color-mix(in srgb, {{ $isWaitlisted ? 'var(--ig-warning)' : 'var(--ig-success)' }} 15%, transparent);
                                 padding:3px 10px;border-radius:var(--ig-radius-full);">
                        {{ $isWaitlisted ? 'Lista d\'attesa' : 'Confermato' }}
                    </span>
                </div>
            @empty
                <p style="font-size:var(--ig-text-sm);color:var(--ig-text-3);margin:0;">
                    Nessun corso prenotato.
                </p>
                <div style="margin-top:12px;">
                    <a href="{{ route('athlete.bookings') }}"
                       style="font-size:var(--ig-text-sm);color:var(--ig-accent);text-decoration:none;">
                        Scopri i corsi disponibili →
                    </a>
                </div>
            @endforelse
        </div>

        @if ($pastClassBookings->isNotEmpty())
            <div class="athlete-card">
                <div class="section-title" style="margin-bottom:16px;">STORICO CORSI</div>
                @foreach ($pastClassBookings as $booking)
                    @php
                        $occ = $booking->occurrence;
                        $course = $occ?->groupClass;
                        $statusLabel = match($booking->status) {
                            'confirmed'           => 'Completato',
                            'cancelled_by_athlete'=> 'Annullato',
                            'cancelled_by_gym'    => 'Annullato',
                            'no_show'             => 'Assente',
                            default               => $booking->status,
                        };
                        $statusColor = match($booking->status) {
                            'confirmed' => 'var(--ig-accent)',
                            'no_show'   => 'var(--ig-danger)',
                            default     => 'var(--ig-text-3)',
                        };
                    @endphp
                    <div style="display:flex;align-items:center;justify-content:space-between;
                                padding:12px 0;border-bottom:1px solid var(--ig-border);">
                        <div>
                            <div style="font-size:var(--ig-text-sm);font-weight:600;color:var(--ig-text-2);">
                                {{ $course?->name ?? '—' }}
                            </div>
                            <div style="font-size:var(--ig-text-xs);color:var(--ig-text-3);margin-top:2px;">
                                {{ \Carbon\Carbon::parse($occ?->date)->format('d/m/Y') }}
                            </div>
                        </div>
                        <span style="font-size:var(--ig-text-xs);font-weight:700;color:{{ $statusColor }};
                                     background:color-mix(in srgb, {{ $statusColor }} 15%, transparent);
                                     padding:3px 10px;border-radius:var(--ig-radius-full);">
                            {{ $statusLabel }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    @endif

    {{-- ===== SEZIONE ACCESSI ===== --}}
    @if ($activeSection === 'accessi')
        <div class="athlete-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <div class="section-title">ULTIMI ACCESSI</div>
            </div>

            @forelse ($recentAccessLogs as $log)
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;
                            padding:12px 0;border-bottom:1px solid var(--ig-border);">
                    <div style="flex:1;">
                        <div style="font-size:var(--ig-text-sm);font-weight:600;color:var(--ig-text-1);">
                            {{ $log->checked_in_at->format('d/m/Y') }}
                        </div>
                        <div style="font-size:var(--ig-text-xs);color:var(--ig-text-3);margin-top:2px;">
                            {{ $log->checked_in_at->format('H:i') }}
                            @if ($log->subscription?->plan)
                                · {{ $log->subscription->plan->name }}
                            @endif
                        </div>
                    </div>
                    <div>
                        <span style="font-size:var(--ig-text-xs);font-weight:600;color:var(--ig-success);
                                     background:var(--ig-success-subtle);border-radius:var(--ig-radius-sm);
                                     padding:2px 8px;">
                            Entrata
                        </span>
                    </div>
                </div>
            @empty
                <div style="text-align:center;padding:var(--ig-sp-6) 0;color:var(--ig-text-3);">
                    <p style="font-size:var(--ig-text-base);margin:0;">Nessun accesso registrato.</p>
                </div>
            @endforelse
        </div>
    @endif

    {{-- ===== SEZIONE MESSAGGI ===== --}}
    @if ($activeSection === 'messaggi')
        <div class="athlete-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <div class="section-title">
                    MESSAGGI RECENTI
                    @if ($unreadMessagesCount > 0)
                        <span style="font-size:var(--ig-text-xs);font-weight:700;color:var(--ig-danger);
                                     margin-left:6px;">{{ $unreadMessagesCount }} non letti</span>
                    @endif
                </div>
                <a href="{{ route('athlete.messages') }}"
                   style="font-size:var(--ig-text-xs);font-weight:600;color:var(--ig-accent);text-decoration:none;">
                    Apri messaggi
                </a>
            </div>

            @forelse ($recentMessages as $msg)
                @php
                    $isSent = $msg->sender_id === auth()->id();
                    $contact = $isSent ? $msg->receiver : $msg->sender;
                    $isUnread = ! $isSent && $msg->read_at === null;
                @endphp
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;
                            padding:12px 0;border-bottom:1px solid var(--ig-border);">
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;align-items:center;gap:6px;margin-bottom:2px;">
                            <span style="font-size:var(--ig-text-sm);font-weight:{{ $isUnread ? '700' : '600' }};
                                         color:{{ $isUnread ? 'var(--ig-text-1)' : 'var(--ig-text-2)' }};">
                                {{ $isSent ? 'Tu → ' : '' }}{{ $contact?->name ?? '—' }}
                            </span>
                            @if ($isUnread)
                                <span style="width:7px;height:7px;border-radius:50%;
                                             background:var(--ig-danger);flex-shrink:0;"></span>
                            @endif
                        </div>
                        <div style="font-size:var(--ig-text-xs);color:var(--ig-text-3);
                                    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:220px;">
                            {{ Str::limit($msg->body, 60) }}
                        </div>
                    </div>
                    <div style="font-size:var(--ig-text-xs);color:var(--ig-text-3);flex-shrink:0;">
                        {{ $msg->created_at->format('d/m H:i') }}
                    </div>
                </div>
            @empty
                <div style="text-align:center;padding:var(--ig-sp-6) 0;color:var(--ig-text-3);">
                    <p style="font-size:var(--ig-text-base);margin:0;">Nessun messaggio.</p>
                    <a href="{{ route('athlete.messages') }}"
                       style="font-size:var(--ig-text-sm);color:var(--ig-accent);text-decoration:none;
                              display:inline-block;margin-top:8px;">
                        Scrivi al tuo trainer →
                    </a>
                </div>
            @endforelse

            @if ($recentMessages->isNotEmpty())
                <div style="text-align:center;margin-top:14px;">
                    <a href="{{ route('athlete.messages') }}"
                       style="font-size:var(--ig-text-sm);color:var(--ig-accent);text-decoration:none;">
                        Vai ai messaggi →
                    </a>
                </div>
            @endif
        </div>
    @endif

    {{-- ===== SEZIONE PASSWORD ===== --}}
    @if ($activeSection === 'password')
        <form wire:submit="updatePassword">
            <div class="athlete-card">
                <div class="section-title" style="margin-bottom:16px;">CAMBIA PASSWORD</div>

                @if ($passwordMessage)
                    <div style="background:var(--ig-success-subtle);border:1px solid rgba(34,197,94,.3);border-radius:8px;
                                padding:10px 14px;margin-bottom:16px;font-size:14px;color:var(--ig-success);">
                        {{ $passwordMessage }}
                    </div>
                @endif

                <div style="margin-bottom:16px;">
                    <label class="ig-form-label">Password attuale</label>
                    <input type="password" wire:model="currentPassword" autocomplete="current-password"
                           class="ig-form-input {{ $errors->has('currentPassword') ? 'is-invalid' : '' }}">
                    @error('currentPassword') <span class="ig-field-error">{{ $message }}</span> @enderror
                </div>

                <div style="margin-bottom:16px;">
                    <label class="ig-form-label">Nuova password</label>
                    <input type="password" wire:model="newPassword" autocomplete="new-password"
                           class="ig-form-input {{ $errors->has('newPassword') ? 'is-invalid' : '' }}">
                    @error('newPassword') <span class="ig-field-error">{{ $message }}</span> @enderror
                </div>

                <div style="margin-bottom:20px;">
                    <label class="ig-form-label">Conferma nuova password</label>
                    <input type="password" wire:model="newPasswordConfirmation" autocomplete="new-password"
                           class="ig-form-input {{ $errors->has('newPasswordConfirmation') ? 'is-invalid' : '' }}">
                    @error('newPasswordConfirmation') <span class="ig-field-error">{{ $message }}</span> @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled"
                        style="width:100%;background:var(--ig-accent);color:#fff;border:none;border-radius:8px;
                               padding:13px;font-size:15px;font-weight:600;cursor:pointer;">
                    <span wire:loading.remove wire:target="updatePassword">Aggiorna password</span>
                    <span wire:loading wire:target="updatePassword">Aggiornamento...</span>
                </button>
            </div>
        </form>
    @endif

    {{-- ===== STRUMENTI SVILUPPO (solo local) ===== --}}
    @if(app()->environment('local'))
    <div class="ig-devtools-card"
         x-data="{ desktop: localStorage.getItem('ig-viewport') === 'desktop' }">
        <div class="section-title" style="margin-bottom:8px;">STRUMENTI SVILUPPO</div>
        <p class="ig-devtools-desc">
            Forza layout desktop per revisione grafica dallo stesso dispositivo mobile.
            Limiti noti: safe-area inset non applicata, DPR del device invariato,
            rendering non identico a desktop reale.
        </p>
        <button type="button"
                class="ig-devtools-btn"
                :aria-pressed="desktop"
                :aria-label="desktop ? 'Ripristina layout mobile' : 'Forza layout desktop'"
                @click="
                    if (desktop) {
                        localStorage.removeItem('ig-viewport');
                    } else {
                        localStorage.setItem('ig-viewport', 'desktop');
                    }
                    location.reload();
                ">
            <span x-text="desktop ? 'Ripristina layout mobile' : 'Forza layout desktop'"></span>
        </button>
    </div>
    @endif

    {{-- ===== SEZIONE ACCOUNT / DANGER ===== --}}
    @if ($activeSection === 'danger')
        <div class="athlete-card" style="border:1px solid #2a1a1a;">
            <div class="section-title" style="margin-bottom:8px;color:var(--ig-danger);">ELIMINA ACCOUNT</div>
            <p style="font-size:13px;color:var(--ig-text-2);margin-bottom:16px;line-height:1.5;">
                Una volta eliminato, tutti i tuoi dati saranno rimossi definitivamente. Questa operazione non è reversibile.
            </p>

            <form wire:submit="deleteAccount"
                  x-data="{ confirm: false }"
                  x-on:submit.prevent="if(confirm) $wire.deleteAccount(); else confirm = true">

                <div x-show="confirm" style="margin-bottom:16px;">
                    <label class="ig-form-label">Conferma con la tua password</label>
                    <input type="password" wire:model="currentPassword" autocomplete="current-password"
                           placeholder="La tua password attuale"
                           class="ig-form-input is-invalid">
                    @error('currentPassword') <span class="ig-field-error">{{ $message }}</span> @enderror
                </div>

                <button type="submit"
                        style="width:100%;background:var(--ig-danger);color:#fff;border:none;border-radius:8px;
                               padding:13px;font-size:15px;font-weight:600;cursor:pointer;">
                    <span x-text="confirm ? 'Conferma eliminazione account' : 'Elimina il mio account'"></span>
                </button>

                <button type="button" x-show="confirm" @click="confirm = false"
                        style="width:100%;margin-top:8px;background:transparent;color:var(--ig-text-2);
                               border:1px solid var(--ig-border);border-radius:8px;padding:11px;font-size:14px;cursor:pointer;">
                    Annulla
                </button>
            </form>
        </div>
    @endif
</div>
