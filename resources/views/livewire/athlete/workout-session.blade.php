<div x-data x-init="window._igWire = $wire; navigator.onLine && $store.syncQueue.flush($wire)">
    {{-- Timer globale + sync queue Alpine stores --}}
    <script>
    // ---- IndexedDB helpers ----
    const _igDb = (() => {
        let _p = null;
        return () => {
            if (_p) return _p;
            _p = new Promise((resolve, reject) => {
                const req = indexedDB.open('iron-gym-sync', 1);
                req.onupgradeneeded = (e) => {
                    const db = e.target.result;
                    if (!db.objectStoreNames.contains('queue')) {
                        db.createObjectStore('queue', { keyPath: 'client_uuid' });
                    }
                };
                req.onsuccess = (e) => resolve(e.target.result);
                req.onerror = (e) => reject(e.target.error);
            });
            return _p;
        };
    })();

    function _igPut(record) {
        return _igDb().then(db => new Promise((res, rej) => {
            const tx = db.transaction('queue', 'readwrite');
            tx.objectStore('queue').put(record);
            tx.oncomplete = res;
            tx.onerror = e => rej(e.target.error);
        }));
    }

    function _igGetAll() {
        return _igDb().then(db => new Promise((res, rej) => {
            const tx = db.transaction('queue', 'readonly');
            const req = tx.objectStore('queue').getAll();
            req.onsuccess = e => res(e.target.result);
            req.onerror = e => rej(e.target.error);
        }));
    }

    function _igDelete(clientUuid) {
        return _igDb().then(db => new Promise((res, rej) => {
            const tx = db.transaction('queue', 'readwrite');
            tx.objectStore('queue').delete(clientUuid);
            tx.oncomplete = res;
            tx.onerror = e => rej(e.target.error);
        }));
    }

    document.addEventListener('alpine:init', () => {

        // ---- syncQueue store ----
        Alpine.store('syncQueue', {
            pendingSetIds: {},
            isOnline: navigator.onLine,
            _flushing: false,
            _retryDelay: 2000,

            async enqueue(operation, payload) {
                const op = {
                    client_uuid: crypto.randomUUID(),
                    operation,
                    client_timestamp: Date.now(),
                    payload,
                    status: 'pending',
                };
                await _igPut(op);
                if (payload.set_id) {
                    this.pendingSetIds = { ...this.pendingSetIds, [payload.set_id]: true };
                }
            },

            isPending(setId) {
                return !!this.pendingSetIds[setId];
            },

            async flush(wire) {
                if (this._flushing || !navigator.onLine) return;
                this._flushing = true;

                try {
                    const all = await _igGetAll();
                    const pending = all.filter(op => op.status === 'pending');
                    if (pending.length === 0) { this._flushing = false; return; }

                    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
                    const resp = await fetch('{{ route('athlete.session.sync') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({ operations: pending }),
                    });

                    if (!resp.ok) {
                        this._scheduleRetry(wire);
                        return;
                    }

                    const data = await resp.json();
                    for (const result of data.results) {
                        await _igDelete(result.client_uuid);
                        const op = pending.find(o => o.client_uuid === result.client_uuid);
                        if (op?.payload?.set_id) {
                            const updated = { ...this.pendingSetIds };
                            delete updated[op.payload.set_id];
                            this.pendingSetIds = updated;
                        }
                    }

                    this._retryDelay = 2000;
                    if (wire) { wire.$refresh(); }
                } catch (_) {
                    this._scheduleRetry(wire);
                } finally {
                    this._flushing = false;
                }
            },

            _scheduleRetry(wire) {
                setTimeout(() => this.flush(wire), this._retryDelay);
                this._retryDelay = Math.min(this._retryDelay * 2, 30000);
            },
        });

        // Il $wire è disponibile solo dopo mount; usiamo un ref globale impostato da x-init
        window.addEventListener('online', () => {
            Alpine.store('syncQueue').isOnline = true;
            if (window._igWire) { Alpine.store('syncQueue').flush(window._igWire); }
        });
        window.addEventListener('offline', () => {
            Alpine.store('syncQueue').isOnline = false;
        });

        // ---- restTimer store ----
        Alpine.store('restTimer', {
            running: false,
            seconds: 0,
            _intervalId: null,
            _totalSec: 0,
            start(sec) {
                if (!sec || sec <= 0) return;
                clearInterval(this._intervalId);
                this.seconds = sec;
                this._totalSec = sec;
                this.running = true;
                this._intervalId = setInterval(() => {
                    if (this.seconds <= 0) {
                        clearInterval(this._intervalId);
                        this.running = false;
                        this._onDone();
                    } else {
                        this.seconds--;
                    }
                }, 1000);
            },
            skip() {
                clearInterval(this._intervalId);
                this.running = false;
                this.seconds = 0;
            },
            _onDone() {
                if (navigator.vibrate) navigator.vibrate([300, 150, 300]);
                if (typeof Notification !== 'undefined' && Notification.permission === 'granted') {
                    new Notification('Recupero completato!', { body: 'Torna in pista.' });
                }
            },
            fmt(s) {
                const m = Math.floor(s / 60);
                const sec = s % 60;
                return m + ':' + String(sec).padStart(2, '0');
            }
        });
    });
    </script>

    @push('styles')
    <style>
        /* Nascondi la bottom nav durante la sessione per dare spazio alla zona azione */
        .bottom-nav { display: none !important; }
        body { padding-bottom: 0 !important; }
    </style>
    @endpush

    {{-- Header progresso sticky --}}
    <div class="ws-progress-header">
        <div class="ws-progress-info">
            <div>
                <span class="ws-progress-name">{{ $session->name }}</span>
                <span style="font-size:var(--ig-text-xs);color:var(--ig-text-3);margin-left:var(--ig-sp-2);">
                    Settimana {{ $session->week->week_number }}
                </span>
            </div>
            <div style="display:flex;align-items:center;gap:var(--ig-sp-3);">
                <span class="ws-progress-count">
                    {{ $currentGroupIndex + 1 }}/{{ $totalGroups }}
                    &bull;
                    {{ $completedSets }}/{{ $totalSets }} set
                </span>
                <button wire:click="completeSession"
                        wire:confirm="Terminare la sessione ora? I set non completati verranno ignorati."
                        style="background:transparent;border:1px solid var(--ig-border);color:var(--ig-text-2);
                               font-size:var(--ig-text-xs);font-weight:600;padding:8px 12px;border-radius:var(--ig-radius-sm);
                               cursor:pointer;white-space:nowrap;min-height:var(--ig-touch-target);">
                    Termina
                </button>
            </div>
        </div>
        @php $pct = $totalSets > 0 ? round($completedSets / $totalSets * 100) : 0; @endphp
        <div class="ws-progress-bar">
            <div class="ws-progress-bar-fill" style="width:{{ $pct }}%;"></div>
        </div>
    </div>

    {{-- Focus su un esercizio alla volta --}}
    @if ($currentGroup->isNotEmpty())
        @include('livewire.athlete.partials.session-exercise', ['exercises' => $currentGroup])
    @else
        <div style="padding:var(--ig-sp-8) var(--ig-sp-4);text-align:center;color:var(--ig-text-3);">
            Nessun esercizio in questa sessione.
        </div>
    @endif

    {{-- Barra navigazione esercizi --}}
    @php
        $prevGroupName = null;
        $nextGroupName = null;
        if ($currentGroupIndex > 0 && isset($groupedExercises[$currentGroupIndex - 1])) {
            $prevGroupName = collect($groupedExercises[$currentGroupIndex - 1])->map(fn($e) => $e->exercise->name_it)->implode(' + ');
        }
        if ($currentGroupIndex < $totalGroups - 1 && isset($groupedExercises[$currentGroupIndex + 1])) {
            $nextGroupName = collect($groupedExercises[$currentGroupIndex + 1])->map(fn($e) => $e->exercise->name_it)->implode(' + ');
        }
    @endphp

    <div class="ws-nav-bar" x-data="{ jumpOpen: false }">
        <button wire:click="prevGroup"
                @disabled($currentGroupIndex === 0)
                class="ws-nav-btn"
                aria-label="Esercizio precedente">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>

        <button @click="jumpOpen = true" class="ws-nav-list-btn">
            <span style="font-size:var(--ig-text-xs);font-weight:700;color:var(--ig-text-2);">
                {{ $currentGroupIndex + 1 }} / {{ $totalGroups }}
            </span>
            <span class="ws-nav-list-btn-name" style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                {{ $currentGroup->map(fn($e) => $e->exercise->name_it)->implode(' + ') }}
            </span>
            <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <button wire:click="nextGroup"
                @disabled($currentGroupIndex >= $totalGroups - 1)
                class="ws-nav-btn"
                aria-label="Esercizio successivo">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </button>

        {{-- Jump drawer (bottom sheet) --}}
        <div x-show="jumpOpen" x-cloak class="ws-jump-drawer" role="dialog" aria-modal="true" aria-label="Lista esercizi">
            <div class="ws-jump-backdrop" @click="jumpOpen = false"></div>
            <div class="ws-jump-sheet" @click.stop x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="transform translate-y-full" x-transition:enter-end="transform translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="transform translate-y-0" x-transition:leave-end="transform translate-y-full">
                <div class="ws-jump-handle" aria-hidden="true"></div>
                <div class="ws-jump-title">Salta a...</div>
                @foreach ($groupedExercises as $gi => $grp)
                    @php
                        $grpCol = collect($grp);
                        $grpName = $grpCol->map(fn($e) => $e->exercise->name_it)->implode(' + ');
                        $grpDone = $grpCol->every(fn($se) => $se->sets->where('is_warmup', false)->whereNull('completed_at')->isEmpty());
                        $grpTotal = $grpCol->sum(fn($se) => $se->sets->where('is_warmup', false)->count());
                        $grpCompleted = $grpCol->sum(fn($se) => $se->sets->where('is_warmup', false)->whereNotNull('completed_at')->count());
                    @endphp
                    <button @click="$wire.jumpToGroup({{ $gi }}); jumpOpen = false;"
                            class="ws-jump-item {{ $gi === $currentGroupIndex ? 'ws-jump-item--active' : '' }}"
                            style="width:100%;background:none;border:none;text-align:left;">
                        <span class="ws-jump-item-num">{{ $gi + 1 }}</span>
                        <span class="ws-jump-item-name">{{ $grpName }}</span>
                        <span class="ws-jump-item-status">
                            @if ($grpDone)
                                <svg style="width:14px;height:14px;color:var(--ig-success);" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                {{ $grpCompleted }}/{{ $grpTotal }}
                            @endif
                        </span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Zona azione fissa in basso --}}
    @php
        $actionSet  = null;
        $actionSe   = null;
        foreach ($currentGroup as $se) {
            $firstIncomplete = $se->sets->where('is_warmup', false)->whereNull('completed_at')->sortBy('set_index')->first();
            if ($firstIncomplete) {
                $actionSet = $firstIncomplete;
                $actionSe  = $se;
                break;
            }
        }
        $currentGroupAllDone = $actionSet === null;

        // Verifica se tutti i gruppi sono completati
        $allGroupsDone = true;
        foreach ($groupedExercises as $grp) {
            foreach (collect($grp) as $se) {
                if ($se->sets->where('is_warmup', false)->whereNull('completed_at')->isNotEmpty()) {
                    $allGroupsDone = false;
                    break 2;
                }
            }
        }

        if ($actionSe) {
            $actionRestSec    = $actionSe->technique_type === 'cluster'
                                ? ($actionSe->intra_cluster_rest_sec ?? $actionSe->planned_rest_sec)
                                : $actionSe->planned_rest_sec;
            $actionRestSecJs  = $actionRestSec !== null ? (int) $actionRestSec : 'null';
            $actionMeasure    = $actionSe->exercise->measurement_type ?? 'reps_weight';
            $actionWorkingSets = $actionSe->sets->where('is_warmup', false)->sortBy('set_index')->values();
            $actionSetIndex   = $actionWorkingSets->search(fn($s) => $s->id === $actionSet->id) + 1;
            $actionSetTotal   = $actionWorkingSets->count();
            $actionPrevPerf   = $previousPerformance[$actionSe->exercise_id][$actionSet->set_index] ?? null;
        }
    @endphp

    <div x-data="{ pending: false }" class="ws-action-zone">

        {{-- Rest timer (integrato nella zona azione) --}}
        <div x-data x-show="$store.restTimer.running" x-cloak class="ws-action-timer">
            {{-- SR: annuncia il conto alla rovescia ogni 10s circa (polite, non interrompe) --}}
            <span class="sr-only" aria-live="polite" aria-atomic="true"
                  x-text="$store.restTimer.running ? $store.restTimer.fmt($store.restTimer.seconds) + ' al recupero' : ''"></span>
            <div>
                <div style="font-size:var(--ig-text-xs);color:var(--ig-text-3);text-transform:uppercase;
                            font-weight:700;letter-spacing:.05em;margin-bottom:2px;">Recupero</div>
                <div class="ws-action-timer-time" x-text="$store.restTimer.fmt($store.restTimer.seconds)" aria-hidden="true"></div>
            </div>
            <div style="flex:1;height:4px;background:var(--ig-border);border-radius:2px;overflow:hidden;margin:0 var(--ig-sp-3);">
                <div style="height:100%;background:var(--ig-accent);border-radius:2px;transition:width .9s linear;"
                     x-bind:style="'width:' + ($store.restTimer.seconds / ($store.restTimer._totalSec || 1) * 100) + '%'"></div>
            </div>
            <button @click="$store.restTimer.skip()" class="ws-action-timer-skip">Salta</button>
        </div>

        @if ($currentGroupAllDone)
            {{-- Tutti i set del gruppo completati --}}
            <div class="ws-action-done">
                <div class="ws-action-done-msg">
                    <svg style="width:20px;height:20px;color:var(--ig-success);display:inline;vertical-align:middle;margin-right:4px;" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    {{ $currentGroup->map(fn($e) => $e->exercise->name_it)->implode(' + ') }} completato
                </div>
                @if ($allGroupsDone)
                    <x-athlete.button variant="primary" :full="true" wire:click="completeSession"
                                      wire:loading.attr="disabled">
                        <span wire:loading.remove>Completa sessione</span>
                        <span wire:loading>Salvataggio...</span>
                    </x-athlete.button>
                @else
                    <x-athlete.button variant="secondary" :full="true" wire:click="nextGroup">
                        Prossimo esercizio
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true" style="display:inline;vertical-align:middle;margin-left:4px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </x-athlete.button>
                @endif
            </div>

        @elseif ($actionSet)
            {{-- Input per il set corrente --}}
            <div class="ws-action-info">
                <span class="ws-action-set-label">
                    {{ $actionSe->exercise->name_it }} &bull; Set {{ $actionSetIndex }}/{{ $actionSetTotal }}
                </span>
                @if ($actionPrevPerf && ($actionPrevPerf['reps'] !== null || $actionPrevPerf['weight'] !== null))
                    <span class="ws-action-prev">
                        prec:
                        @if ($actionPrevPerf['weight'] !== null) {{ $actionPrevPerf['weight'] }}kg @endif
                        @if ($actionPrevPerf['reps'] !== null) &times; {{ $actionPrevPerf['reps'] }} @endif
                        @if ($actionPrevPerf['rir'] !== null) RIR{{ $actionPrevPerf['rir'] }} @endif
                    </span>
                @endif
            </div>

            <div class="ws-action-inputs">
                @if (in_array($actionMeasure, ['reps_weight', 'reps_only', 'time_weight']))
                    <div class="ws-action-input-group">
                        <span class="ws-action-input-label" aria-hidden="true">Reps</span>
                        <x-athlete.input-number
                            wire:model="setData.{{ $actionSet->id }}.reps"
                            mode="numeric"
                            min="0"
                            step="1"
                            :stepper="true"
                            placeholder="{{ $actionSet->planned_reps ?? '0' }}"
                            aria-label="Ripetizioni"
                        />
                    </div>
                @endif
                @if (in_array($actionMeasure, ['reps_weight', 'time_weight']))
                    <div class="ws-action-input-group ws-action-input-group--kg">
                        <span class="ws-action-input-label" aria-hidden="true">Kg</span>
                        <x-athlete.input-number
                            wire:model="setData.{{ $actionSet->id }}.weight"
                            mode="decimal"
                            min="0"
                            step="2.5"
                            :stepper="true"
                            placeholder="{{ $actionSet->planned_weight_kg ?? '0' }}"
                            aria-label="Peso in kg"
                        />
                    </div>
                @endif
                @if (in_array($actionMeasure, ['time', 'isometric_hold']))
                    <div class="ws-action-input-group" style="flex:2;">
                        <span class="ws-action-input-label" aria-hidden="true">Secondi</span>
                        <x-athlete.input-number
                            wire:model="setData.{{ $actionSet->id }}.duration"
                            mode="numeric"
                            min="0"
                            step="5"
                            :stepper="true"
                            placeholder="{{ $actionSet->planned_duration_sec ?? '0' }}"
                            aria-label="Durata in secondi"
                        />
                    </div>
                @endif
                @if (in_array($actionMeasure, ['reps_weight', 'reps_only', 'time_weight']))
                    <div class="ws-action-input-group">
                        <span class="ws-action-input-label" aria-hidden="true">RIR</span>
                        <x-athlete.input-number
                            wire:model="setData.{{ $actionSet->id }}.rir"
                            mode="numeric"
                            min="0"
                            max="10"
                            step="1"
                            :stepper="true"
                            placeholder="{{ $actionSet->planned_rir ?? '—' }}"
                            aria-label="Reps in riserva"
                        />
                    </div>
                @endif
            </div>

            <div class="ws-action-btn">
                <button @click="
                            const setId = {{ $actionSet->id }};
                            const restSec = {{ $actionRestSecJs }};
                            pending = true;
                            if (!navigator.onLine) {
                                const d = $wire.__instance?.snapshot?.memo?.data ?? {};
                                const sd = (d.setData ?? {})[setId] ?? {};
                                $store.syncQueue.enqueue('complete_set', {
                                    set_id: setId,
                                    reps: sd.reps !== '' ? parseInt(sd.reps) : null,
                                    weight: sd.weight !== '' ? parseFloat(sd.weight) : null,
                                    rir: sd.rir !== '' ? parseInt(sd.rir) : null,
                                    duration: sd.duration !== '' ? parseInt(sd.duration) : null,
                                });
                                pending = false;
                                if (restSec) { $store.restTimer.start(restSec); }
                            } else {
                                $wire.completeSet(setId).then(() => {
                                    pending = false;
                                    if (restSec) { $store.restTimer.start(restSec); }
                                });
                            }
                        "
                        :disabled="pending"
                        class="ws-action-done-btn">
                    <span x-show="!pending">
                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                             style="display:inline;vertical-align:middle;margin-right:6px;" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Fatto
                    </span>
                    <span x-show="pending" x-cloak>
                        <span class="ig-spinner"></span>
                    </span>
                </button>
            </div>

        @endif
    </div>


    {{-- Modale storico esercizio --}}
    @if ($exerciseHistoryId !== null)
        <div style="position:fixed;inset:0;z-index:400;background:rgba(0,0,0,.85);display:flex;align-items:center;justify-content:center;">
            <div style="background:#1A1A1A;border-radius:16px;width:50%;max-height:80vh;overflow-y:auto;
                        padding:20px 20px 24px;"
                 @click.stop>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <h3 style="margin:0;font-size:16px;font-weight:700;">{{ $exerciseHistoryName }}</h3>
                    <button wire:click="$set('exerciseHistoryId', null)"
                            style="background:none;border:none;color:#666;font-size:22px;cursor:pointer;line-height:1;min-width:var(--ig-touch-target);min-height:var(--ig-touch-target);display:flex;align-items:center;justify-content:center;"
                            aria-label="Chiudi">&times;</button>
                </div>
                @if ($this->exerciseHistory->isEmpty())
                    <p style="color:#666;font-size:13px;text-align:center;padding:24px 0;">Nessuna sessione precedente trovata.</p>
                @else
                    @foreach ($this->exerciseHistory as $pastSession)
                        <div style="margin-bottom:16px;">
                            <p style="font-size:11px;color:#666;font-weight:700;text-transform:uppercase;
                                      letter-spacing:.05em;margin-bottom:6px;">
                                {{ $pastSession->session->scheduled_date?->format('d/m/Y') ?? '—' }}
                            </p>
                            @foreach ($pastSession->sets->where('is_warmup', false)->whereNotNull('completed_at')->sortBy('set_index') as $pastSet)
                                <div style="font-size:13px;color:#ccc;padding:3px 0;display:flex;gap:8px;">
                                    <span style="color:#555;min-width:16px;">{{ $pastSet->set_index }}</span>
                                    @if ($pastSet->actual_reps) <span>{{ $pastSet->actual_reps }}r</span> @endif
                                    @if ($pastSet->actual_weight_kg) <span>&times; {{ $pastSet->actual_weight_kg }}kg</span> @endif
                                    @if ($pastSet->actual_rir !== null) <span style="color:#666;">RIR{{ $pastSet->actual_rir }}</span> @endif
                                    @if ($pastSet->actual_duration_sec) <span>{{ $pastSet->actual_duration_sec }}s</span> @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    @endif

    {{-- Modale plate calculator --}}
    @if ($plateModalSetId !== null)
        <div style="position:fixed;inset:0;z-index:400;background:rgba(0,0,0,.85);display:flex;align-items:flex-end;"
             wire:click="closePlateModal">
            <div style="background:#1A1A1A;border-radius:16px 16px 0 0;width:100%;max-height:90vh;overflow-y:auto;
                        padding:20px 20px calc(24px + env(safe-area-inset-bottom));"
                 @click.stop>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <h3 style="margin:0;font-size:16px;font-weight:700;">Calcola dischi</h3>
                    <button wire:click="closePlateModal"
                            style="background:none;border:none;color:#666;font-size:22px;cursor:pointer;line-height:1;min-width:var(--ig-touch-target);min-height:var(--ig-touch-target);display:flex;align-items:center;justify-content:center;"
                            aria-label="Chiudi">&times;</button>
                </div>

                <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                    <label style="font-size:13px;color:#aaa;">Peso bilanciere (kg)</label>
                    <input type="number" step="0.5" min="0" inputmode="decimal"
                           wire:model.live="plateBarWeight"
                           style="background:#2A2A2A;border:1px solid #3A3A3A;border-radius:8px;
                                  color:#fff;padding:6px 10px;width:80px;font-size:16px;text-align:center;">
                    <button wire:click="calculatePlates" class="btn-accent" style="padding:8px 16px;font-size:13px;">
                        Calcola
                    </button>
                </div>

                @if ($plateLoadout !== null)
                    <div style="margin-bottom:12px;">
                        <p style="font-size:12px;color:#666;margin-bottom:8px;">
                            Obiettivo: <strong style="color:#fff;">{{ $plateLoadout['target_kg'] }} kg</strong>
                            &bull;
                            Caricato: <strong style="color:{{ $plateLoadout['delta_kg'] == 0 ? '#22c55e' : '#f59e0b' }};">
                                {{ $plateLoadout['loaded_kg'] }} kg
                            </strong>
                            @if ($plateLoadout['delta_kg'] != 0)
                                <span style="color:#f59e0b;">({{ $plateLoadout['delta_kg'] > 0 ? '+' : '' }}{{ $plateLoadout['delta_kg'] }} kg)</span>
                            @endif
                        </p>

                        @if (count($plateLoadout['plates']) > 0)
                            <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">
                                @foreach ($plateLoadout['plates'] as $plate)
                                    @for ($pi = 0; $pi < $plate['count']; $pi++)
                                        <div style="background:{{ $plate['color'] ?? '#555' }};width:40px;height:40px;
                                                    border-radius:50%;display:flex;align-items:center;justify-content:center;
                                                    font-size:10px;font-weight:700;color:#fff;border:2px solid rgba(255,255,255,.2);">
                                            {{ $plate['weight_kg'] }}
                                        </div>
                                    @endfor
                                @endforeach
                            </div>
                            <p style="font-size:11px;color:#666;">Per lato del bilanciere</p>
                        @else
                            <p style="color:#666;font-size:13px;">Solo bilanciere nudo ({{ $plateLoadout['bar_kg'] }} kg).</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Modale sostituzione esercizio --}}
    @if ($substitutingSeId !== null)
        <div x-data="{ open: true }" x-show="open"
             style="position:fixed;inset:0;z-index:400;background:rgba(0,0,0,.85);display:flex;align-items:center;justify-content:center;"
             role="dialog" aria-modal="true" aria-labelledby="modal-sost-title">
            <div style="background:#1A1A1A;border-radius:16px;width:min(90%,480px);max-height:90vh;overflow-y:auto;
                        padding:20px;"
                 @click.stop>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <h3 id="modal-sost-title" style="margin:0;font-size:16px;font-weight:700;">Sostituisci esercizio</h3>
                    <button wire:click="closeSubstitutionModal"
                            style="background:none;border:none;color:#666;font-size:22px;cursor:pointer;line-height:1;min-width:var(--ig-touch-target);min-height:var(--ig-touch-target);display:flex;align-items:center;justify-content:center;"
                            aria-label="Chiudi">&times;</button>
                </div>
                <p style="font-size:12px;color:#666;margin-bottom:16px;">Alternative con lo stesso pattern motorio, ordinate per sovrapposizione muscolare.</p>

                @if (count($substitutionCandidates) > 0)
                    @foreach ($substitutionCandidates as $candidate)
                        <div style="background:#262626;border-radius:12px;padding:14px;margin-bottom:10px;">
                            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:8px;gap:8px;">
                                <div style="flex:1;">
                                    <p style="margin:0 0 6px;font-size:14px;font-weight:700;color:#fff;">{{ $candidate['name_it'] }}</p>
                                    <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:6px;">
                                        @foreach ($candidate['equipment_slugs'] as $eqSlug)
                                            <span style="font-size:10px;background:#1A1A1A;border:1px solid #333;color:#888;
                                                         padding:2px 7px;border-radius:999px;">{{ $eqSlug }}</span>
                                        @endforeach
                                    </div>
                                    @if (count($candidate['primary_muscles']) > 0)
                                        <p style="margin:0;font-size:11px;color:#666;">
                                            Primari: {{ implode(', ', $candidate['primary_muscles']) }}
                                        </p>
                                    @endif
                                </div>
                                <div style="text-align:right;flex-shrink:0;">
                                    <div style="font-size:10px;color:#555;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;">Overlap</div>
                                    <div style="font-size:18px;font-weight:700;color:#FF6B00;">{{ $candidate['overlap'] }}%</div>
                                </div>
                            </div>
                            <button wire:click="confirmSubstitution('{{ $candidate['slug'] }}')"
                                    wire:loading.attr="disabled"
                                    @click="open = false"
                                    style="width:100%;background:#FF6B00;border:none;border-radius:8px;padding:9px;
                                           font-size:13px;font-weight:700;color:#fff;cursor:pointer;min-height:var(--ig-touch-target);">
                                Usa questo esercizio
                            </button>
                        </div>
                    @endforeach
                @else
                    <p style="text-align:center;color:#666;padding:24px 0;font-size:13px;">
                        Nessuna alternativa trovata con lo stesso pattern e tipo di misurazione.
                    </p>
                @endif
                <button wire:click="closeSubstitutionModal"
                        style="width:100%;margin-top:12px;background:none;border:1px solid #333;border-radius:8px;
                               padding:9px;font-size:13px;font-weight:600;color:#888;cursor:pointer;
                               min-height:var(--ig-touch-target);">
                    Annulla
                </button>
            </div>
        </div>
    @endif

    {{-- Modale readiness pre-sessione --}}
    @if ($showReadinessModal)
    <div x-data="{
            sleep: 2, stress: 2, soreness: 2, joint: 2, note: '',
            labels: {
                sleep:    ['Pessimo', 'Scarso', 'Buono', 'Ottimo'],
                stress:   ['Minimo', 'Basso', 'Moderato', 'Elevato'],
                soreness: ['Nessuno', 'Lieve', 'Moderato', 'Forte'],
                joint:    ['Dolore', 'Fastidio', 'Ok', 'Perfetto'],
            },
            colorFor(val) {
                return ['#ef4444','#f59e0b','#3b82f6','#22c55e'][val] ?? '#3b82f6';
            }
         }"
         style="position:fixed;inset:0;z-index:1100;background:rgba(0,0,0,.85);
                display:flex;align-items:flex-end;justify-content:center;">

        <div style="background:#1A1A1A;border-radius:16px 16px 0 0;width:100%;max-width:480px;
                    padding:24px 20px;padding-bottom:max(24px, env(safe-area-inset-bottom));
                    max-height:95vh;overflow-y:auto;"
             @click.stop>

            <h3 style="margin:0 0 4px;font-size:18px;font-weight:700;color:#fff;">Come stai oggi?</h3>
            <p style="font-size:12px;color:#666;margin:0 0 20px;">Check rapido pre-allenamento — aiuta il sistema a modulare i carichi.</p>

            @foreach ([
                ['key' => 'sleep',    'label' => 'Sonno'],
                ['key' => 'stress',   'label' => 'Stress'],
                ['key' => 'soreness', 'label' => 'Indolenzimento muscolare'],
                ['key' => 'joint',    'label' => 'Articolazioni'],
            ] as $field)
            <div style="margin-bottom:16px;">
                <p style="font-size:12px;font-weight:600;color:#aaa;margin:0 0 8px;text-transform:uppercase;letter-spacing:.05em;">
                    {{ $field['label'] }}
                </p>
                <div style="display:flex;gap:8px;">
                    @foreach ([0,1,2,3] as $v)
                    <button @click="{{ $field['key'] }} = {{ $v }}"
                            x-bind:style="{{ $field['key'] }} === {{ $v }} ? 'background:' + colorFor({{ $v }}) + ';color:#000;border-color:transparent;' : ''"
                            style="flex:1;padding:10px 4px;border-radius:10px;border:1px solid #333;
                                   background:#262626;color:#ccc;font-size:11px;font-weight:700;cursor:pointer;
                                   transition:background .15s;min-height:var(--ig-touch-target);">
                        <span x-text="labels.{{ $field['key'] }}[{{ $v }}]"></span>
                    </button>
                    @endforeach
                </div>
            </div>
            @endforeach

            <div style="margin-bottom:20px;">
                <textarea x-model="note" placeholder="Note (opzionale)..."
                          style="width:100%;background:#262626;border:1px solid #333;border-radius:10px;
                                 color:#ccc;font-size:13px;padding:10px 12px;resize:none;outline:none;box-sizing:border-box;"
                          rows="2"></textarea>
            </div>

            <div style="display:flex;gap:10px;">
                <button @click="$wire.submitReadiness(sleep, stress, soreness, joint, note)"
                        wire:loading.attr="disabled"
                        class="btn-accent"
                        style="flex:1;">
                    Inizia allenamento
                </button>
                <button @click="$wire.skipReadiness()"
                        style="background:transparent;border:1px solid #333;color:#666;
                               padding:12px 16px;border-radius:10px;font-size:13px;cursor:pointer;white-space:nowrap;min-height:var(--ig-touch-target);">
                    Salta il check
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modale proposta modulazione carichi --}}
    @if ($showModulationProposal && count($modulationProposal) > 0)
    <div style="position:fixed;inset:0;z-index:1100;background:rgba(0,0,0,.85);
                display:flex;align-items:flex-end;justify-content:center;">

        <div style="background:#1A1A1A;border-radius:16px 16px 0 0;width:100%;max-width:480px;
                    padding:24px 20px;padding-bottom:max(24px, env(safe-area-inset-bottom));
                    max-height:95vh;overflow-y:auto;"
             @click.stop>

            <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
                <div style="background:#FF6B00;border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 style="margin:0;font-size:18px;font-weight:700;color:#fff;">Modulazione carichi</h3>
            </div>

            <p style="font-size:13px;color:#aaa;margin:0 0 6px;">
                Score: <strong style="color:#FF6B00;">{{ $modulationProposal['score'] }}/12</strong>
            </p>
            <p style="font-size:13px;color:#ccc;margin:0 0 16px;">{{ $modulationProposal['suggestion'] }}</p>

            @if ($modulationProposal['includesJointAlert'])
            <div style="background:#7f1d1d;border-radius:10px;padding:10px 14px;margin-bottom:16px;font-size:12px;color:#fca5a5;">
                Dolore articolare rilevato — segnala al trainer prima di procedere.
            </div>
            @endif

            @if (count($modulationProposal['sets']) > 0)
            <div style="background:#1E1E1E;border-radius:10px;padding:14px;margin-bottom:14px;">
                <p style="font-size:11px;font-weight:700;color:#666;text-transform:uppercase;letter-spacing:.05em;margin:0 0 10px;">
                    Carichi proposti (-{{ $modulationProposal['reduction_pct'] }}%)
                </p>
                @foreach ($modulationProposal['sets'] as $item)
                <div style="display:flex;justify-content:space-between;align-items:center;
                            padding:6px 0;border-bottom:1px solid #2A2A2A;font-size:13px;">
                    <span style="color:#aaa;">
                        {{ $item['exercise_name'] }} · set {{ $item['set_index'] }}
                    </span>
                    <span>
                        <span style="color:#666;text-decoration:line-through;">{{ $item['original_weight'] }} kg</span>
                        <span style="color:#FF6B00;margin-left:8px;font-weight:700;">{{ $item['proposed_weight'] }} kg</span>
                    </span>
                </div>
                @endforeach
            </div>
            @endif

            @if (count($modulationProposal['sets_to_remove']) > 0)
            <div style="background:#1E1E1E;border-radius:10px;padding:14px;margin-bottom:14px;">
                <p style="font-size:11px;font-weight:700;color:#666;text-transform:uppercase;letter-spacing:.05em;margin:0 0 10px;">
                    Set rimossi (riduzione volume)
                </p>
                @foreach ($modulationProposal['sets_to_remove'] as $item)
                <div style="font-size:13px;color:#aaa;padding:4px 0;">
                    {{ $item['exercise_name'] }} · set {{ $item['set_index'] }}
                </div>
                @endforeach
            </div>
            @endif

            <div style="display:flex;gap:10px;">
                <button wire:click="acceptModulation"
                        wire:loading.attr="disabled"
                        class="btn-accent"
                        style="flex:1;">
                    Applica modifiche
                </button>
                <button wire:click="rejectModulation"
                        style="background:transparent;border:1px solid #333;color:#aaa;
                               padding:12px 16px;border-radius:10px;font-size:13px;cursor:pointer;white-space:nowrap;min-height:var(--ig-touch-target);">
                    Allena al piano
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Form feedback --}}
    <div x-data="{ open: {{ $showFeedback ? 'true' : 'false' }} }"
         @open-feedback.window="open = true">
        <div x-show="open" x-transition style="position:fixed;inset:0;z-index:200;background:rgba(0,0,0,.7);display:flex;align-items:flex-end;">
            <div x-show="open" @click.outside="open = false"
                 style="background:#1E1E1E;border-radius:16px 16px 0 0;padding:24px 20px;width:100%;max-height:90vh;overflow-y:auto;">
                <livewire:athlete.session-feedback-form :session="$session" />
            </div>
        </div>
    </div>
</div>
