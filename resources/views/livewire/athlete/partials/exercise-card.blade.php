{{--
  Partial per una card esercizio dentro la sessione workout.
  Variabili: $exercise (SessionExercise con sets, exercise caricati)
--}}
@php
    $measurementType = $exercise->exercise->measurement_type;
    $restSec = $exercise->technique_type === 'cluster'
        ? ($exercise->intra_cluster_rest_sec ?? $exercise->planned_rest_sec)
        : $exercise->planned_rest_sec;
    $restSecJs = $restSec !== null ? (int) $restSec : 'null';

    $workingSets = $exercise->sets->where('is_warmup', false)->sortBy('set_index');
    $warmupSets  = $exercise->sets->where('is_warmup', true)->sortBy('set_index');

    $firstWorkingWeight = $workingSets->first()?->planned_weight_kg;
    $hasWarmupSets      = $warmupSets->isNotEmpty();
    $canGenerateWarmup  = $measurementType === 'reps_weight'
        && $firstWorkingWeight !== null
        && ! $hasWarmupSets;
@endphp

<div style="margin-bottom:{{ $loop->last ? '0' : '16px' }};">

    {{-- Header esercizio --}}
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
        <button wire:click="showExerciseHistory({{ $exercise->exercise_id }}, '{{ addslashes($exercise->exercise->name_it) }}')"
                style="font-size:16px;font-weight:600;flex:1;background:none;border:none;color:inherit;
                       padding:0;text-align:left;cursor:pointer;text-decoration:underline dotted;
                       text-underline-offset:3px;">{{ $exercise->exercise->name_it }}</button>
        @if ($exercise->technique_type !== 'straight')
            <span style="font-size:10px;background:#2A2A2A;color:#FF6B00;padding:2px 8px;border-radius:999px;font-weight:700;">
                {{ $this->techniqueLabel($exercise->technique_type) }}
            </span>
        @endif
        <button wire:click="showExerciseDetail({{ $exercise->exercise_id }})"
                aria-label="Dettagli esercizio"
                style="background:#2A2A2A;border:1px solid #3A3A3A;border-radius:8px;padding:4px 10px;
                       font-size:11px;font-weight:600;color:#aaa;cursor:pointer;white-space:nowrap;
                       display:flex;align-items:center;gap:4px;line-height:1.4;">
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16v-4M12 8h.01"/>
            </svg>
            Info
        </button>
    </div>

    @if ($exercise->exercise->execution_description)
        <div x-data="{ open: false }" style="margin-bottom:10px;">
            <button type="button" @click="open = !open"
                    style="background:none;border:none;padding:0;color:#888;font-size:11px;
                           cursor:pointer;display:flex;align-items:center;gap:4px;">
                <svg x-bind:style="open ? 'transform:rotate(90deg)' : ''" style="width:10px;height:10px;transition:transform .2s;fill:#888;" viewBox="0 0 20 20"><path d="M7 5l6 5-6 5V5z"/></svg>
                <span x-text="open ? 'Nascondi esecuzione' : 'Come eseguire'"></span>
            </button>
            <p x-show="open" x-cloak
               style="font-size:12px;color:#999;margin-top:6px;line-height:1.5;padding:8px;
                      background:#1A1A1A;border-radius:6px;border-left:2px solid #FF6B00;">
                {{ $exercise->exercise->execution_description }}
            </p>
        </div>
    @endif

    @if ($exercise->trainer_note)
        <p style="font-size:12px;color:#666;font-style:italic;margin-bottom:10px;">
            {{ $exercise->trainer_note }}
        </p>
    @endif

    {{-- Bottone genera riscaldamento --}}
    @if ($canGenerateWarmup)
        <div x-data="{ warmupQueued: false }" style="margin-bottom:10px;">
            <button @click="
                if (!navigator.onLine) {
                    warmupQueued = true;
                    $store.syncQueue.enqueue('generate_warmup', { session_exercise_id: {{ $exercise->id }} });
                } else {
                    $wire.generateWarmup({{ $exercise->id }});
                }
            "
                    :disabled="warmupQueued"
                    style="background:transparent;border:1px dashed #444;border-radius:8px;
                           padding:6px 14px;font-size:12px;color:#888;cursor:pointer;
                           display:flex;align-items:center;gap:6px;">
                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <span x-text="warmupQueued ? 'In attesa di sync...' : 'Genera riscaldamento'"></span>
            </button>
        </div>
    @endif

    {{-- Header colonne — la larghezza azioni si adatta se l'esercizio usa bilanciere --}}
    @php $usesBell = $this->exerciseUsesBarbell($exercise->id); @endphp
    <div style="display:grid;grid-template-columns:24px 1fr 62px 62px 52px {{ $usesBell ? '96px' : '72px' }};gap:4px;align-items:center;
                font-size:10px;color:#555;font-weight:700;text-transform:uppercase;letter-spacing:.04em;
                padding:0 2px;margin-bottom:4px;">
        <span>#</span>
        <span>Piano</span>
        <span style="text-align:center;">Reps</span>
        <span style="text-align:center;">Kg</span>
        <span style="text-align:center;">RIR</span>
        <span></span>
    </div>

    {{-- Set di riscaldamento --}}
    @foreach ($warmupSets as $set)
        <div x-data="{ done: {{ $set->completed_at ? 'true' : 'false' }}, pending: $store.syncQueue.isPending({{ $set->id }}) }"
             style="display:grid;grid-template-columns:24px 1fr 62px 62px 52px {{ $usesBell ? '96px' : '72px' }};gap:4px;align-items:center;
                    padding:7px 2px;border-bottom:1px solid #222;"
             :style="done ? 'opacity:.5' : ''">

            <span style="font-size:11px;color:#555;font-weight:700;">W</span>

            <span style="font-size:12px;color:#666;">
                @if ($set->planned_reps) {{ $set->planned_reps }}r @endif
                @if ($set->planned_weight_kg) {{ $set->planned_weight_kg }}kg @endif
                <template x-if="pending">
                    <span style="font-size:10px;color:#F59E0B;margin-left:4px;" title="In attesa di sync">⏳</span>
                </template>
            </span>

            <input type="number" min="0"
                   wire:model="setData.{{ $set->id }}.reps"
                   class="workout-input"
                   placeholder="{{ $set->planned_reps ?? '—' }}">

            <input type="number" min="0" step="0.5"
                   wire:model="setData.{{ $set->id }}.weight"
                   class="workout-input"
                   placeholder="{{ $set->planned_weight_kg ?? '—' }}">

            <span></span>

            <div style="display:flex;align-items:center;gap:4px;">
                <template x-if="!done">
                    <button @click="
                        done = true;
                        if (!navigator.onLine) {
                            pending = true;
                            $store.syncQueue.enqueue('quick_log', { set_id: {{ $set->id }} });
                            if ({{ $restSecJs }}) { $store.restTimer.start({{ $restSecJs }}); }
                        } else {
                            $wire.quickLog({{ $set->id }}).then(() => { if ({{ $restSecJs }}) { $store.restTimer.start({{ $restSecJs }}); } });
                        }
                    "
                            style="flex:1;background:#2A2A2A;border:1px solid #3A3A3A;border-radius:6px;
                                   height:30px;font-size:11px;font-weight:600;color:#aaa;cursor:pointer;">
                        Fatto
                    </button>
                </template>
                <template x-if="done">
                    <svg style="width:18px;height:18px;color:#22c55e;flex-shrink:0;" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </template>
                <button @click="
                    if (!navigator.onLine) {
                        $el.closest('[x-data]').style.display = 'none';
                        $store.syncQueue.enqueue('delete_warmup', { set_id: {{ $set->id }} });
                    } else {
                        $wire.deleteWarmupSet({{ $set->id }});
                    }
                "
                        aria-label="Rimuovi set riscaldamento"
                        style="background:none;border:none;color:#555;font-size:16px;cursor:pointer;
                               padding:0 2px;line-height:1;flex-shrink:0;">&times;</button>
            </div>
        </div>
    @endforeach

    {{-- Working set --}}
    @php $usesBell = $this->exerciseUsesBarbell($exercise->id); @endphp
    @foreach ($workingSets as $set)
        @php
            $prevPerf = $this->previousPerformance[$exercise->exercise_id][$set->set_index] ?? null;
        @endphp

        <div x-data="{ done: {{ $set->completed_at ? 'true' : 'false' }}, pending: $store.syncQueue.isPending({{ $set->id }}) }"
             style="display:grid;grid-template-columns:24px 1fr 62px 62px 52px {{ ($usesBell) ? '96px' : '72px' }};gap:4px;align-items:center;
                    padding:7px 2px;border-bottom:1px solid #2A2A2A;"
             :style="done ? 'opacity:.6' : ''">

            <span style="font-size:13px;color:#666;">{{ $set->set_index }}</span>

            <span style="font-size:12px;color:#888;">
                @if ($set->planned_reps) {{ $set->planned_reps }}r @endif
                @if ($set->planned_weight_kg) {{ $set->planned_weight_kg }}kg @endif
                @if ($set->planned_rir !== null) RIR{{ $set->planned_rir }} @endif
                @if ($set->planned_duration_sec) {{ $set->planned_duration_sec }}s @endif
                <template x-if="pending">
                    <span style="font-size:10px;color:#F59E0B;margin-left:4px;" title="In attesa di sync">⏳</span>
                </template>
            </span>

            {{-- Input reps --}}
            @if (in_array($measurementType, ['reps_weight', 'reps_only', 'time_weight']))
                <input type="number" min="0"
                       wire:model="setData.{{ $set->id }}.reps"
                       class="workout-input"
                       placeholder="{{ $set->planned_reps ?? '—' }}">
            @else
                <span></span>
            @endif

            {{-- Input peso --}}
            @if (in_array($measurementType, ['reps_weight', 'time_weight']))
                <input type="number" min="0" step="0.5"
                       wire:model="setData.{{ $set->id }}.weight"
                       class="workout-input"
                       placeholder="kg">
            @elseif (in_array($measurementType, ['time', 'isometric_hold']))
                <input type="number" min="0"
                       wire:model="setData.{{ $set->id }}.duration"
                       class="workout-input"
                       placeholder="{{ $set->planned_duration_sec ?? 's' }}">
            @else
                <span></span>
            @endif

            {{-- Input RIR --}}
            @if (in_array($measurementType, ['reps_weight', 'reps_only', 'time_weight']))
                <input type="number" min="0" max="10"
                       wire:model="setData.{{ $set->id }}.rir"
                       class="workout-input"
                       placeholder="{{ $set->planned_rir ?? '—' }}">
            @else
                <span></span>
            @endif

            {{-- Azione --}}
            <div style="display:flex;align-items:center;gap:4px;">
                {{-- Bottone plate calculator: visibile solo per esercizi con bilanciere e con peso pianificato --}}
                @if ($set->planned_weight_kg && $usesBell)
                    <button wire:click="openPlateModal({{ $set->id }})"
                            aria-label="Calcola dischi"
                            style="background:#2A2A2A;border:1px solid #3A3A3A;border-radius:6px;
                                   width:28px;height:28px;display:flex;align-items:center;justify-content:center;
                                   cursor:pointer;flex-shrink:0;padding:0;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#FF6B00" stroke-width="2">
                            <rect x="2" y="10" width="4" height="4" rx="1"/>
                            <rect x="18" y="10" width="4" height="4" rx="1"/>
                            <rect x="6" y="8" width="3" height="8" rx="1"/>
                            <rect x="15" y="8" width="3" height="8" rx="1"/>
                            <line x1="9" y1="12" x2="15" y2="12"/>
                        </svg>
                    </button>
                @endif
                <template x-if="!done">
                    <button @click="
                        done = true;
                        if (!navigator.onLine) {
                            pending = true;
                            $store.syncQueue.enqueue('quick_log', { set_id: {{ $set->id }} });
                            if ({{ $restSecJs }}) { $store.restTimer.start({{ $restSecJs }}); }
                        } else {
                            $wire.quickLog({{ $set->id }}).then(() => { if ({{ $restSecJs }}) { $store.restTimer.start({{ $restSecJs }}); } });
                        }
                    "
                            style="flex:1;background:#FF6B00;border:none;border-radius:6px;
                                   height:32px;font-size:12px;font-weight:700;color:#fff;cursor:pointer;">
                        Fatto
                    </button>
                </template>
                <template x-if="done">
                    <div style="display:flex;align-items:center;gap:4px;flex:1;">
                        <svg style="width:20px;height:20px;color:#22c55e;flex-shrink:0;" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <button @click="
                            if (!navigator.onLine) {
                                pending = true;
                                const d = $wire.__instance?.snapshot?.memo?.data ?? {};
                                const sd = (d.setData ?? {})[{{ $set->id }}] ?? {};
                                $store.syncQueue.enqueue('complete_set', {
                                    set_id: {{ $set->id }},
                                    reps: sd.reps !== '' ? parseInt(sd.reps) : null,
                                    weight: sd.weight !== '' ? parseFloat(sd.weight) : null,
                                    rir: sd.rir !== '' ? parseInt(sd.rir) : null,
                                    duration: sd.duration !== '' ? parseInt(sd.duration) : null,
                                });
                            } else {
                                $wire.completeSet({{ $set->id }});
                            }
                        "
                                style="background:none;border:none;color:#555;font-size:11px;
                                       cursor:pointer;padding:0;text-decoration:underline;">
                            Salva
                        </button>
                    </div>
                </template>
            </div>
        </div>

        {{-- Riga performance precedente --}}
        @if ($prevPerf && ($prevPerf['reps'] !== null || $prevPerf['weight'] !== null))
            <div style="padding:3px 28px 5px;font-size:11px;color:#444;">
                prec:
                @if ($prevPerf['weight'] !== null) {{ $prevPerf['weight'] }} kg &times; @endif
                @if ($prevPerf['reps'] !== null) {{ $prevPerf['reps'] }} @endif
                @if ($prevPerf['rir'] !== null) &bull; RIR {{ $prevPerf['rir'] }} @endif
            </div>
        @endif
    @endforeach
</div>
