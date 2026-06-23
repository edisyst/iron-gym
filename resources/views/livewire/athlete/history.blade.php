<div>
    <h1 style="font-size:20px;font-weight:700;margin-bottom:16px;">Storico</h1>

    {{-- Filtro mesociclo --}}
    <div class="athlete-card" style="padding:12px 14px;margin-bottom:16px;">
        <select wire:model.live="mesocycleId"
                style="background:#2A2A2A;border:1px solid #333;border-radius:6px;
                       color:#fff;padding:8px 10px;width:100%;font-size:14px;">
            <option value="">Tutti i mesocicli</option>
            @foreach ($mesocycles as $meso)
                <option value="{{ $meso->id }}">{{ $meso->name }}</option>
            @endforeach
        </select>
    </div>

    @forelse ($sessions as $session)
        {{-- Card sessione --}}
        <div class="athlete-card" style="margin-bottom:12px;">
            <div wire:click="showDetail({{ $session->id }})"
                 style="cursor:pointer;display:flex;align-items:center;gap:12px;">
                <div style="flex:1;min-width:0;">
                    <p style="font-size:15px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        {{ $session->name }}
                    </p>
                    <p style="font-size:12px;color:#666;margin-top:3px;">
                        {{ $session->completed_at?->format('d/m/Y') }}
                        @php $dur = $this->duration($session); @endphp
                        @if ($dur) &bull; {{ $dur }} @endif
                        &bull; {{ $session->week->mesocycle->name }}
                        &bull; {{ $this->completedSetsCount($session) }} set
                    </p>
                </div>
                <svg style="width:18px;height:18px;color:#555;flex-shrink:0;
                     transition:transform .2s;{{ $selectedSessionId === $session->id ? 'transform:rotate(90deg)' : '' }}"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </div>

            {{-- Pannello dettaglio --}}
            @if ($selectedSessionId === $session->id && $this->selectedSession !== null)
                <div style="margin-top:16px;border-top:1px solid #2A2A2A;padding-top:16px;">
                    @foreach ($this->selectedSession->sessionExercises as $exercise)
                        <div style="margin-bottom:16px;">
                            <button wire:click="showExerciseHistory({{ $exercise->exercise_id }}, '{{ addslashes($exercise->exercise->name_it) }}')"
                                    style="font-size:14px;font-weight:600;margin-bottom:8px;color:#ccc;
                                           background:none;border:none;padding:0;text-align:left;cursor:pointer;
                                           text-decoration:underline dotted;text-underline-offset:3px;">
                                {{ $exercise->exercise->name_it }}
                            </button>

                            @foreach ($exercise->sets->sortBy('set_index')->whereNotNull('actual_reps') as $set)
                                <div style="display:flex;gap:12px;font-size:13px;color:#888;
                                            padding:4px 0;border-bottom:1px solid #222;">
                                    <span style="color:#666;width:24px;">{{ $set->set_index }}</span>
                                    <span>{{ $set->actual_reps }} reps</span>
                                    @if ($set->actual_weight_kg)
                                        <span>{{ $set->actual_weight_kg }} kg</span>
                                    @endif
                                    @if ($set->actual_rir !== null)
                                        <span>RIR {{ $set->actual_rir }}</span>
                                    @endif
                                    @if ($set->estimated_1rm)
                                        <span style="color:#FF6B00;margin-left:auto;">
                                            e1RM {{ $set->estimated_1rm }} kg
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <div class="athlete-card" style="text-align:center;padding:32px 16px;">
            <p style="color:#666;">Nessuna sessione completata.</p>
        </div>
    @endforelse

    {{-- Modal storico esercizio --}}
    @if ($exerciseHistoryId !== null)
        <div style="position:fixed;inset:0;z-index:300;background:rgba(0,0,0,.8);display:flex;align-items:flex-end;">
            <div style="background:#1E1E1E;border-radius:16px 16px 0 0;padding:20px 16px;width:100%;max-height:85vh;overflow-y:auto;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <p style="font-size:15px;font-weight:700;color:#fff;">{{ $exerciseHistoryName }}</p>
                    <button wire:click="showExerciseHistory({{ $exerciseHistoryId }}, '')"
                            style="background:none;border:none;color:#666;font-size:22px;line-height:1;cursor:pointer;">&times;</button>
                </div>

                @forelse ($this->exerciseHistory as $se)
                    <div style="margin-bottom:16px;">
                        <p style="font-size:12px;color:#FF6B00;font-weight:600;margin-bottom:6px;">
                            {{ $se->session->completed_at?->format('d/m/Y') }} &bull; {{ $se->session->name }}
                        </p>
                        @foreach ($se->sets->whereNotNull('actual_reps') as $set)
                            <div style="display:flex;gap:10px;font-size:13px;color:#888;
                                        padding:3px 0;border-bottom:1px solid #222;">
                                <span style="color:#555;width:20px;">{{ $set->set_index }}</span>
                                <span>{{ $set->actual_reps }} reps</span>
                                @if ($set->actual_weight_kg)
                                    <span>{{ $set->actual_weight_kg }} kg</span>
                                @endif
                                @if ($set->actual_rir !== null)
                                    <span>RIR {{ $set->actual_rir }}</span>
                                @endif
                                @if ($set->estimated_1rm)
                                    <span style="color:#FF6B00;margin-left:auto;">e1RM {{ $set->estimated_1rm }} kg</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @empty
                    <p style="color:#666;text-align:center;padding:24px 0;">Nessuna sessione precedente.</p>
                @endforelse
            </div>
        </div>
    @endif

    {{-- Paginazione --}}
    @if ($sessions->hasPages())
        <div style="margin-top:16px;display:flex;gap:8px;justify-content:center;">
            @if ($sessions->onFirstPage())
                <span style="color:#444;padding:8px 14px;border:1px solid #333;border-radius:6px;">&#8249;</span>
            @else
                <button wire:click="previousPage" style="background:#2A2A2A;color:#fff;border:1px solid #333;
                        border-radius:6px;padding:8px 14px;cursor:pointer;">&#8249;</button>
            @endif
            <span style="color:#888;padding:8px 14px;">{{ $sessions->currentPage() }} / {{ $sessions->lastPage() }}</span>
            @if ($sessions->hasMorePages())
                <button wire:click="nextPage" style="background:#2A2A2A;color:#fff;border:1px solid #333;
                        border-radius:6px;padding:8px 14px;cursor:pointer;">&#8250;</button>
            @else
                <span style="color:#444;padding:8px 14px;border:1px solid #333;border-radius:6px;">&#8250;</span>
            @endif
        </div>
    @endif
</div>
