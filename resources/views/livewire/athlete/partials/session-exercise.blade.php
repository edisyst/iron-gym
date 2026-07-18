{{--
  Partial: session-exercise.blade.php
  Mostra il focus su un esercizio (o un gruppo superset) nella sessione.
  Variabili ricevute: $exercises (Collection<SessionExercise>, già caricate con sets e exercise)
  Il resto è accessibile via $this (Livewire) e variabili del parent ($previousPerformance, $setData)
--}}

@php
    $isGroup = $exercises->first()?->group_id !== null;
    $group   = $isGroup ? $exercises->first()->group : null;
@endphp

{{-- Etichetta gruppo superset / giant set --}}
@if ($isGroup && $group)
    <div style="padding:0 var(--ig-sp-4) var(--ig-sp-3);">
        <span class="ws-group-label">
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4M4 17h12m0 0l-4-4m4 4l-4 4"/>
            </svg>
            {{ $group->group_type === 'superset' ? 'Superset' : 'Giant set' }}
            &bull; {{ $group->rounds }} round
        </span>
    </div>
@endif

@foreach ($exercises->sortBy('order_in_group') as $exercise)
    @php
        $measurementType    = $exercise->exercise->measurement_type;
        $workingSets        = $exercise->sets->where('is_warmup', false)->sortBy('set_index');
        $warmupSets         = $exercise->sets->where('is_warmup', true)->sortBy('set_index');
        $firstWorkingWeight = $workingSets->first()?->planned_weight_kg;
        $canGenerateWarmup  = $measurementType === 'reps_weight'
                              && $firstWorkingWeight !== null
                              && $warmupSets->isEmpty();
        $hasCompletedSets   = $workingSets->whereNotNull('completed_at')->isNotEmpty();
        $canSubstitute      = ! $hasCompletedSets;
        $usesBell           = $this->exerciseUsesBarbell($exercise->id);
        $firstIncompleteId  = $workingSets->whereNull('completed_at')->first()?->id;
        $restSec            = $exercise->technique_type === 'cluster'
                              ? ($exercise->intra_cluster_rest_sec ?? $exercise->planned_rest_sec)
                              : $exercise->planned_rest_sec;
        $restSecJs          = $restSec !== null ? (int) $restSec : 'null';
    @endphp

    {{-- Separatore tra esercizi in un gruppo --}}
    @if (! $loop->first)
        <hr class="ws-exercise-divider">
    @endif

    <div class="ws-exercise-focus">

        {{-- Badge tecnica e sostituzione --}}
        @if ($exercise->technique_type !== 'straight' || ($exercise->substituted_from_exercise_id !== null && $exercise->substitutedFrom !== null))
            <div class="ws-exercise-badges">
                @if ($exercise->technique_type !== 'straight')
                    <x-athlete.badge status="accent">{{ $this->techniqueLabel($exercise->technique_type) }}</x-athlete.badge>
                @endif
                @if ($exercise->substituted_from_exercise_id !== null && $exercise->substitutedFrom !== null)
                    <span class="ws-substituted-from">
                        <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4M4 17h12m0 0l-4-4m4 4l-4 4"/>
                        </svg>
                        Sost. da: {{ $exercise->substitutedFrom->name_it }}
                    </span>
                @endif
            </div>
        @endif

        {{-- Nome esercizio + bottoni azione --}}
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:var(--ig-sp-3);margin-bottom:var(--ig-sp-3);">
            <button wire:click="showExerciseHistory({{ $exercise->exercise_id }}, '{{ addslashes($exercise->exercise->name_it) }}')"
                    class="ws-exercise-name"
                    aria-label="Storico {{ $exercise->exercise->name_it }}"
                    style="background:none;border:none;color:var(--ig-text-1);padding:0;text-align:left;
                           cursor:pointer;flex:1;text-decoration:underline dotted;text-underline-offset:3px;">
                {{ $exercise->exercise->name_it }}
            </button>
            <div class="ws-exercise-btns">
@if ($canSubstitute)
                    <button wire:click="openSubstitutionModal({{ $exercise->id }})"
                            class="ws-icon-btn" aria-label="Sostituisci {{ $exercise->exercise->name_it }}">
                        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4M4 17h12m0 0l-4-4m4 4l-4 4"/>
                        </svg>
                        Sost.
                    </button>
                @endif
            </div>
        </div>

        {{-- Immagine esercizio con toggle --}}
        @php
            $exSlug = $exercise->exercise->slug;
            $imgFile = public_path('images/exercises/' . $exSlug . '.png');
            $imgSrc  = file_exists($imgFile)
                ? asset('images/exercises/' . $exSlug . '.png')
                : asset('images/exercises/no-image.svg');
        @endphp
        <div x-data="{ imgVisible: true }">
            <button type="button"
                    @click="imgVisible = !imgVisible"
                    class="ws-img-toggle-btn"
                    :aria-pressed="imgVisible.toString()">
                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <template x-if="imgVisible">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-5 0-9-4-9-7s4-7 9-7a10.05 10.05 0 011.875.175M15 12a3 3 0 11-6 0 3 3 0 016 0zm6.364-3.364l-1.414-1.414M3 3l18 18"/>
                    </template>
                    <template x-if="!imgVisible">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </template>
                </svg>
                <span x-text="imgVisible ? 'Nascondi foto' : 'Mostra foto'" style="font-size:var(--ig-text-xs);"></span>
            </button>
            <div x-show="imgVisible" x-cloak class="ws-exercise-img-wrap">
                <img src="{{ $imgSrc }}"
                     alt="{{ $exercise->exercise->name_it }}"
                     class="ws-exercise-img">
            </div>
        </div>

        {{-- Esecuzione collassabile (include muscoli, attrezzatura e descrizione) --}}
        @if ($exercise->exercise->execution_description || $exercise->exercise->muscles->isNotEmpty() || $exercise->exercise->equipment->isNotEmpty())
            <div x-data="{ open: false }" class="ws-exec-toggle">
                <button type="button" @click="open = !open" class="ws-exec-btn">
                    <svg :style="open ? 'transform:rotate(90deg)' : ''"
                         style="width:10px;height:10px;transition:transform .2s;fill:currentColor;" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M7 5l6 5-6 5V5z"/>
                    </svg>
                    <span x-text="open ? 'Nascondi' : 'Come eseguire'"></span>
                </button>
                <div x-show="open" x-cloak>
                    {{-- Muscoli primari --}}
                    @if ($exercise->exercise->muscles->where('pivot.role', 'primary')->isNotEmpty())
                        <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:var(--ig-sp-2);">
                            @foreach ($exercise->exercise->muscles->where('pivot.role', 'primary') as $m)
                                <span class="ws-muscle-chip ws-muscle-chip--primary">{{ $m->name_it }}</span>
                            @endforeach
                            @foreach ($exercise->exercise->muscles->where('pivot.role', 'secondary') as $m)
                                <span class="ws-muscle-chip ws-muscle-chip--secondary">{{ $m->name_it }}</span>
                            @endforeach
                        </div>
                    @endif
                    {{-- Meccanica, livello, attrezzatura --}}
                    @if ($exercise->exercise->mechanic || $exercise->exercise->skill_level || $exercise->exercise->equipment->isNotEmpty())
                        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:var(--ig-sp-2);">
                            @if ($exercise->exercise->mechanic)
                                <span class="ws-meta-chip">{{ $exercise->exercise->mechanic }}</span>
                            @endif
                            @if ($exercise->exercise->skill_level)
                                <span class="ws-meta-chip">{{ $exercise->exercise->skill_level }}</span>
                            @endif
                            @foreach ($exercise->exercise->equipment as $eq)
                                <span class="ws-meta-chip">{{ $eq->slug }}</span>
                            @endforeach
                        </div>
                    @endif
                    {{-- Descrizione esecuzione --}}
                    @if ($exercise->exercise->execution_description)
                        <p class="ws-exec-text" style="margin-top:0;">{{ $exercise->exercise->execution_description }}</p>
                    @endif
                </div>
            </div>
        @endif

        {{-- Note trainer --}}
        @if ($exercise->trainer_note)
            <p class="ws-trainer-note">&ldquo;{{ $exercise->trainer_note }}&rdquo;</p>
        @endif

        {{-- Riscaldamento --}}
        @if ($warmupSets->isNotEmpty() || $canGenerateWarmup)
            <div class="ws-sets-section">
                <div class="ws-warmup-label">Riscaldamento</div>

                @if ($canGenerateWarmup)
                    <div x-data="{ q: false }" style="margin-bottom:var(--ig-sp-2);">
                        <button @click="
                                    q = true;
                                    if (!navigator.onLine) {
                                        $store.syncQueue.enqueue('generate_warmup', { session_exercise_id: {{ $exercise->id }} });
                                    } else {
                                        $wire.generateWarmup({{ $exercise->id }}).then(() => q = false);
                                    }
                                "
                                :disabled="q"
                                class="ws-warmup-gen-btn">
                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span x-text="q ? 'In attesa...' : 'Genera riscaldamento'"></span>
                        </button>
                    </div>
                @endif

                {{-- Righe warmup --}}
                @foreach ($warmupSets as $wset)
                    <div x-data="{ done: {{ $wset->completed_at ? 'true' : 'false' }}, pending: $store.syncQueue.isPending({{ $wset->id }}) }"
                         class="ws-warmup-row"
                         :class="done ? 'ws-warmup-row--done' : ''">

                        {{-- Info sinistra --}}
                        <div class="ws-warmup-info">
                            <span class="ws-warmup-badge">W</span>
                            <span class="ws-warmup-plan">
                                @if ($wset->planned_reps) {{ $wset->planned_reps }}r @endif
                                @if ($wset->planned_weight_kg) {{ $wset->planned_weight_kg }}kg @endif
                            </span>
                            <template x-if="pending">
                                <span style="font-size:10px;color:var(--ig-warning);" title="In attesa di sync">⏳</span>
                            </template>
                        </div>

                        {{-- Peso modificabile --}}
                        <input type="number" inputmode="decimal" min="0" step="0.5"
                               wire:model="setData.{{ $wset->id }}.weight"
                               class="ws-warmup-weight-input"
                               aria-label="Peso riscaldamento in kg"
                               placeholder="{{ $wset->planned_weight_kg ?? '-' }}">

                        {{-- Conferma --}}
                        <template x-if="!done">
                            <button @click="
                                        done = true;
                                        if (!navigator.onLine) {
                                            pending = true;
                                            $store.syncQueue.enqueue('quick_log', { set_id: {{ $wset->id }} });
                                            if ({{ $restSecJs }}) { $store.restTimer.start({{ $restSecJs }}); }
                                        } else {
                                            $wire.quickLog({{ $wset->id }}).then(() => {
                                                if ({{ $restSecJs }}) { $store.restTimer.start({{ $restSecJs }}); }
                                            });
                                        }
                                    "
                                    class="ws-warmup-confirm-btn"
                                    aria-label="Conferma set riscaldamento">
                                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        </template>
                        <template x-if="done">
                            <svg class="ws-warmup-done-icon" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </template>

                        {{-- Elimina --}}
                        <button @click="
                                    if (!navigator.onLine) {
                                        $el.closest('[x-data]').style.display = 'none';
                                        $store.syncQueue.enqueue('delete_warmup', { set_id: {{ $wset->id }} });
                                    } else {
                                        $wire.deleteWarmupSet({{ $wset->id }});
                                    }
                                "
                                class="ws-warmup-delete-btn"
                                aria-label="Rimuovi set riscaldamento">&times;</button>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Working set: lista senza input (gli input sono nella zona azione fissa) --}}
        <div class="ws-sets-section">
            @foreach ($workingSets as $set)
                @php
                    $isDone    = $set->completed_at !== null;
                    $isActive  = $set->id === $firstIncompleteId;
                    $prevPerf  = $previousPerformance[$exercise->exercise_id][$set->set_index] ?? null;
                @endphp

                <div class="ws-set-row {{ $isDone ? 'ws-set-row--done' : ($isActive ? 'ws-set-row--active' : '') }}">

                    {{-- Indicatore stato --}}
                    <div style="width:20px;flex-shrink:0;display:flex;align-items:center;justify-content:center;">
                        @if ($isDone)
                            <svg style="width:16px;height:16px;color:var(--ig-success);" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @elseif ($isActive)
                            <div class="ws-set-active-dot"></div>
                        @else
                            <span class="ws-set-num">{{ $loop->iteration }}</span>
                        @endif
                    </div>

                    {{-- Piano --}}
                    <div class="ws-set-plan">
                        @if ($set->planned_reps) {{ $set->planned_reps }}r @endif
                        @if ($set->planned_weight_kg) &times; {{ $set->planned_weight_kg }}kg @endif
                        @if ($set->planned_rir !== null) RIR{{ $set->planned_rir }} @endif
                        @if ($set->planned_duration_sec) {{ $set->planned_duration_sec }}s @endif
                        @if ($set->is_warmup === false && $set->set_subtype)
                            <span style="font-size:10px;color:var(--ig-text-3);">({{ $set->set_subtype }})</span>
                        @endif
                    </div>

                    {{-- Eseguito (se completato) --}}
                    @if ($isDone)
                        <div class="ws-set-actual">
                            @if ($set->actual_reps) {{ $set->actual_reps }}r @endif
                            @if ($set->actual_weight_kg) &times; {{ $set->actual_weight_kg }}kg @endif
                            @if ($set->actual_rir !== null) RIR{{ $set->actual_rir }} @endif
                            @if ($set->actual_duration_sec) {{ $set->actual_duration_sec }}s @endif
                        </div>
                        @if ($usesBell && $set->planned_weight_kg)
                            <button wire:click="openPlateModal({{ $set->id }})"
                                    aria-label="Calcola dischi"
                                    class="ws-icon-btn">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <rect x="2" y="10" width="4" height="4" rx="1"/>
                                    <rect x="18" y="10" width="4" height="4" rx="1"/>
                                    <rect x="6" y="8" width="3" height="8" rx="1"/>
                                    <rect x="15" y="8" width="3" height="8" rx="1"/>
                                    <line x1="9" y1="12" x2="15" y2="12"/>
                                </svg>
                            </button>
                        @endif
                    @elseif ($isActive)
                        <span style="font-size:var(--ig-text-xs);color:var(--ig-accent);font-weight:700;white-space:nowrap;">set {{ $loop->iteration }}</span>
                    @endif

                    @if (! $isDone)
                        <button wire:click="skipSet({{ $set->id }})"
                                wire:confirm="Saltare questo set?"
                                class="ws-skip-set-btn"
                                aria-label="Salta set {{ $set->set_index }}">
                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                </div>

                {{-- Performance precedente sotto set attivo --}}
                @if ($isActive && $prevPerf && ($prevPerf['reps'] !== null || $prevPerf['weight'] !== null))
                    <div class="ws-prev-perf">
                        prec:
                        @if ($prevPerf['weight'] !== null) {{ $prevPerf['weight'] }}kg &times; @endif
                        @if ($prevPerf['reps'] !== null) {{ $prevPerf['reps'] }} @endif
                        @if ($prevPerf['rir'] !== null) &bull; RIR{{ $prevPerf['rir'] }} @endif
                    </div>
                @endif
            @endforeach
        </div>

    </div>{{-- /ws-exercise-focus --}}
@endforeach
