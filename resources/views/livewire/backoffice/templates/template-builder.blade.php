<div>
    {{-- Tab settimane + copia settimana --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
        <ul class="nav nav-tabs mb-0">
            @for ($w = 1; $w <= $template->weeks_count; $w++)
                <li class="nav-item">
                    <a href="#"
                       class="nav-link {{ $activeWeek === $w ? 'active' : '' }}"
                       wire:click.prevent="$set('activeWeek', {{ $w }})">
                        Settimana {{ $w }}
                        @if ($w === $template->weeks_count)
                            <span class="badge badge-warning ml-1">Deload</span>
                        @endif
                    </a>
                </li>
            @endfor
        </ul>

        @if ($template->weeks_count > 1)
            <div class="d-flex align-items-center gap-2 ml-3"
                 x-data="{ target: '0' }">
                <small class="text-muted text-nowrap">Copia sett. {{ $activeWeek }} in:</small>
                <select wire:model="copyToWeek"
                        x-model="target"
                        class="form-control form-control-sm"
                        style="width: 110px">
                    <option value="0">— scegli —</option>
                    @for ($w = 1; $w <= $template->weeks_count; $w++)
                        @if ($w !== $activeWeek)
                            <option value="{{ $w }}">Settimana {{ $w }}</option>
                        @endif
                    @endfor
                </select>
                <button type="button"
                        class="btn btn-sm btn-outline-info text-nowrap"
                        wire:click="copyWeek"
                        wire:loading.attr="disabled"
                        :disabled="target === '0'">
                    <i class="fas fa-clone"></i> Copia
                </button>
            </div>
        @endif
    </div>

    <div class="row">
        {{-- Colonna sessioni --}}
        <div class="col-md-9">
            @forelse ($sessions as $session)
                <div class="card mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        {{-- Nome sessione editabile inline --}}
                        <input type="text"
                               class="form-control form-control-sm mr-2"
                               style="max-width: 300px; font-weight: 600"
                               value="{{ $session->name }}"
                               wire:change="updateSessionName({{ $session->id }}, $event.target.value)">
                        <button type="button"
                                class="btn btn-sm btn-outline-danger"
                                wire:click="removeSession({{ $session->id }})"
                                wire:confirm="Eliminare la sessione '{{ $session->name }}' con tutti i suoi esercizi?">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="card-body p-2">

                        {{-- Lista esercizi ordinabile con SortableJS --}}
                        <ul id="sortable-{{ $session->id }}" class="list-unstyled mb-0" style="min-height: 40px">
                            @foreach ($session->templateExercises as $ex)
                                @php
                                    $primaryMuscle = $ex->exercise->muscles
                                        ->filter(fn ($m) => $m->pivot->role === 'primary')
                                        ->sortByDesc(fn ($m) => $m->pivot->contribution_pct)
                                        ->first();
                                @endphp
                                <li data-id="{{ $ex->id }}"
                                    class="mb-2 border rounded p-2 bg-white"
                                    style="{{ $ex->group_key ? 'border-left: 4px solid #007bff !important;' : '' }}">

                                    {{-- Handle + nome esercizio --}}
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-grip-vertical drag-handle text-muted mr-2" style="cursor: grab"></i>
                                            <a href="{{ route('backoffice.exercises.show', $ex->exercise) }}" class="text-dark">
                                                <strong>{{ $ex->exercise->name_it }}</strong>
                                            </a>
                                            @if ($primaryMuscle)
                                                <small class="text-muted ml-2">· {{ $primaryMuscle->name_it }}</small>
                                            @endif
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                wire:click="removeExercise({{ $ex->id }})"
                                                wire:confirm="Rimuovere '{{ $ex->exercise->name_it }}'?">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>

                                    {{-- Riga parametri --}}
                                    <div class="row g-1 align-items-center">
                                        <div class="col-auto">
                                            <label class="mb-0" style="font-size: 0.75em">Tecnica</label>
                                            <select class="form-control form-control-sm"
                                                    wire:change="updateExerciseField({{ $ex->id }}, 'technique_type', $event.target.value)">
                                                @foreach (['straight' => 'Straight', 'drop_set' => 'Drop set', 'rest_pause' => 'Rest-pause', 'myo_reps' => 'Myo-reps', 'cluster' => 'Cluster', 'twenty_ones' => '21s', 'pre_exhaustion' => 'Pre-exhaust', 'emom' => 'EMOM', 'amrap' => 'AMRAP'] as $val => $label)
                                                    <option value="{{ $val }}" {{ $ex->technique_type === $val ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <label class="mb-0" style="font-size: 0.75em">Serie</label>
                                            <input type="number" class="form-control form-control-sm" style="width: 60px"
                                                   value="{{ $ex->planned_sets_count }}" min="1" max="20"
                                                   wire:change="updateExerciseField({{ $ex->id }}, 'planned_sets_count', $event.target.value)">
                                        </div>
                                        <div class="col-auto">
                                            <label class="mb-0" style="font-size: 0.75em">Reps</label>
                                            <input type="number" class="form-control form-control-sm" style="width: 65px"
                                                   value="{{ $ex->planned_reps }}" min="1" max="100"
                                                   wire:change="updateExerciseField({{ $ex->id }}, 'planned_reps', $event.target.value)">
                                        </div>
                                        <div class="col-auto">
                                            <label class="mb-0" style="font-size: 0.75em">RIR</label>
                                            <input type="number" class="form-control form-control-sm" style="width: 58px"
                                                   value="{{ $ex->planned_rir }}" min="0" max="10"
                                                   wire:change="updateExerciseField({{ $ex->id }}, 'planned_rir', $event.target.value)">
                                        </div>
                                        <div class="col-auto">
                                            <label class="mb-0" style="font-size: 0.75em">Riposo (s)</label>
                                            <input type="number" class="form-control form-control-sm" style="width: 75px"
                                                   value="{{ $ex->planned_rest_sec }}" min="0"
                                                   wire:change="updateExerciseField({{ $ex->id }}, 'planned_rest_sec', $event.target.value)">
                                        </div>
                                        <div class="col-auto">
                                            <label class="mb-0" style="font-size: 0.75em">Tempo</label>
                                            <input type="text" class="form-control form-control-sm" style="width: 80px"
                                                   value="{{ $ex->tempo }}" placeholder="3-1-1-0"
                                                   wire:change="updateExerciseField({{ $ex->id }}, 'tempo', $event.target.value)">
                                        </div>
                                    </div>

                                    {{-- Note trainer + raggruppamento --}}
                                    <div x-data="{ showNote: {{ $ex->note ? 'true' : 'false' }}, grouped: {{ $ex->group_key ? 'true' : 'false' }} }" class="mt-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                    @click="showNote = !showNote">
                                                <i class="fas fa-sticky-note"></i> Note
                                            </button>

                                            <div class="form-check mb-0 ml-2">
                                                <input class="form-check-input" type="checkbox"
                                                       id="group_{{ $ex->id }}"
                                                       :checked="grouped"
                                                       @change="
                                                           grouped = $event.target.checked;
                                                           $wire.toggleGroup({{ $ex->id }}, $event.target.checked)
                                                       ">
                                                <label class="form-check-label" for="group_{{ $ex->id }}" style="font-size: 0.8em">
                                                    Raggruppa con successivo
                                                </label>
                                            </div>

                                            @if ($ex->group_key)
                                                <select class="form-control form-control-sm ml-2" style="width: 130px"
                                                        wire:change="updateGroupType({{ $ex->id }}, $event.target.value)">
                                                    <option value="superset" {{ $ex->group_type === 'superset' ? 'selected' : '' }}>Superset</option>
                                                    <option value="giant_set" {{ $ex->group_type === 'giant_set' ? 'selected' : '' }}>Giant set</option>
                                                    <option value="circuit" {{ $ex->group_type === 'circuit' ? 'selected' : '' }}>Circuit</option>
                                                </select>
                                            @endif
                                        </div>

                                        <div x-show="showNote" x-cloak class="mt-2">
                                            <textarea class="form-control form-control-sm" rows="2"
                                                      placeholder="Note per l'atleta..."
                                                      wire:change="updateExerciseField({{ $ex->id }}, 'note', $event.target.value)">{{ $ex->note }}</textarea>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        {{-- Ricerca e aggiunta esercizio inline --}}
                        <div class="mt-2" x-data="{ open: false }">
                            <button type="button" class="btn btn-outline-primary btn-sm w-100"
                                    @click="open = !open">
                                <i class="fas fa-plus"></i> Aggiungi esercizio
                            </button>
                            <div x-show="open" x-cloak class="mt-2 border rounded p-2 bg-light">
                                <input type="text"
                                       wire:model.live.debounce.300ms="exerciseSearch"
                                       class="form-control form-control-sm mb-2"
                                       placeholder="Cerca per nome (min. 2 caratteri)..."
                                       @click.stop>
                                @if (strlen($exerciseSearch) >= 2)
                                    @forelse ($exerciseSearchResults as $result)
                                        @php
                                            $rPrimary = $result->muscles
                                                ->filter(fn ($m) => $m->pivot->role === 'primary')
                                                ->sortByDesc(fn ($m) => $m->pivot->contribution_pct)
                                                ->first();
                                        @endphp
                                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                            <span style="font-size: 0.9em">
                                                {{ $result->name_it }}
                                                @if ($rPrimary)
                                                    <small class="text-muted">· {{ $rPrimary->name_it }}</small>
                                                @endif
                                            </span>
                                            <button type="button" class="btn btn-sm btn-success"
                                                    wire:click="addExerciseById({{ $session->id }}, {{ $result->id }})">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    @empty
                                        <p class="text-muted text-center mb-0" style="font-size: 0.85em">Nessun esercizio trovato.</p>
                                    @endforelse
                                @else
                                    <p class="text-muted mb-0" style="font-size: 0.85em">Digita almeno 2 caratteri per cercare.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4">
                    Nessuna sessione per la settimana {{ $activeWeek }}.
                </div>
            @endforelse

            <button type="button" class="btn btn-outline-secondary" wire:click="addSession">
                <i class="fas fa-plus"></i> Aggiungi sessione alla settimana {{ $activeWeek }}
            </button>
        </div>

        {{-- Sidebar destra: info template --}}
        <div class="col-md-3">
            <div class="card">
                <div class="card-header"><h6 class="card-title mb-0">Info template</h6></div>
                <div class="card-body p-3">
                    <dl class="mb-0" style="font-size: 0.85em">
                        <dt>Obiettivo</dt>
                        <dd>{{ $template->goal }}</dd>
                        <dt>Periodizzazione</dt>
                        <dd>{{ str_replace('_', ' ', $template->periodization_model) }}</dd>
                        <dt>Settimane</dt>
                        <dd>{{ $template->weeks_count }}</dd>
                        <dt>Giorni/sett.</dt>
                        <dd>{{ $template->days_per_week }}</dd>
                    </dl>
                    <hr>
                    <a href="{{ route('backoffice.templates.index') }}" class="btn btn-default btn-sm btn-block">
                        <i class="fas fa-arrow-left"></i> Torna ai template
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@section('js')
@parent
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
    function initSortable() {
        document.querySelectorAll('[id^="sortable-"]').forEach(function (el) {
            if (el._sortable) {
                el._sortable.destroy();
            }
            el._sortable = Sortable.create(el, {
                animation: 150,
                handle: '.drag-handle',
                onEnd: function () {
                    const sessionId = parseInt(el.id.replace('sortable-', ''));
                    const orderedIds = Array.from(el.querySelectorAll('[data-id]'))
                        .map(function (e) { return parseInt(e.dataset.id); });
                    Livewire.dispatch('exercises-reordered', { sessionId: sessionId, orderedIds: orderedIds });
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', initSortable);
    document.addEventListener('livewire:updated', initSortable);
</script>
@endsection
