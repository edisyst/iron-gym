<div>
    {{-- Filtro mesociclo --}}
    <div class="card card-outline card-secondary mb-3">
        <div class="card-body py-2">
            <select wire:model.live="mesocycleId" class="form-control form-control-sm" style="width:300px">
                <option value="">Tutti i mesocicli</option>
                @foreach ($mesocycles as $meso)
                    <option value="{{ $meso->id }}">{{ $meso->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Tabella sessioni --}}
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Sessione</th>
                        <th>Mesociclo</th>
                        <th>Trainer</th>
                        <th>Set</th>
                        <th>Durata</th>
                        <th>Feedback</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sessions as $session)
                        <tr>
                            <td>{{ $session->completed_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>{{ $session->name }}</td>
                            <td>{{ $session->week->mesocycle->name }}</td>
                            <td>{{ $session->week->mesocycle->trainer?->name ?? '—' }}</td>
                            <td>{{ $this->completedSetsCount($session) }} / {{ $this->totalSetsCount($session) }}</td>
                            <td>{{ $this->duration($session) ?? '—' }}</td>
                            <td>
                                @if ($session->feedback)
                                    <i class="fas fa-comment-alt text-success"></i>
                                @else
                                    <i class="fas fa-comment-alt text-muted"></i>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-xs btn-outline-primary"
                                        wire:click="showDetail({{ $session->id }})">
                                    Dettaglio
                                </button>
                            </td>
                        </tr>

                        {{-- Pannello dettaglio inline --}}
                        @if ($selectedSessionId === $session->id && $this->selectedSession !== null)
                            <tr>
                                <td colspan="8" class="bg-light">
                                    <div class="p-3">
                                        @php $s = $this->selectedSession; @endphp

                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <strong>{{ $s->name }}</strong>
                                                <span class="text-muted ml-2">{{ $s->completed_at?->format('d/m/Y H:i') }}</span>
                                                @if ($this->duration($s))
                                                    <span class="text-muted ml-2">{{ $this->duration($s) }}</span>
                                                @endif
                                            </div>
                                            <button class="btn btn-xs btn-secondary" wire:click="showDetail({{ $session->id }})">
                                                Chiudi
                                            </button>
                                        </div>

                                        {{-- Feedback --}}
                                        @if ($s->feedback)
                                            @php
                                                $fb = $s->feedback;
                                                $badgeClass = fn($v) => match((int) $v) {
                                                    0 => 'secondary',
                                                    1 => 'success',
                                                    2 => 'warning',
                                                    3 => 'danger',
                                                    default => 'secondary',
                                                };
                                                $feedbackFields = [
                                                    'pump' => 'Pump',
                                                    'soreness_prev' => 'Indolenzimento residuo',
                                                    'perceived_effort' => 'Sforzo percepito',
                                                    'joint_pain' => 'Dolore articolare',
                                                    'performance' => 'Performance',
                                                ];
                                            @endphp
                                            <div class="mb-3 d-flex flex-wrap gap-2">
                                                @foreach ($feedbackFields as $field => $label)
                                                    <div class="mr-3">
                                                        <small class="text-muted d-block">{{ $label }}</small>
                                                        @if ($fb->$field !== null)
                                                            <span class="badge badge-{{ $badgeClass($fb->$field) }}">{{ $fb->$field }}</span>
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                            @if ($fb->note)
                                                <p class="text-muted small mb-3">
                                                    <i class="fas fa-sticky-note mr-1"></i>{{ $fb->note }}
                                                </p>
                                            @endif
                                        @else
                                            <p class="text-muted small mb-3">Nessun feedback per questa sessione.</p>
                                        @endif

                                        {{-- Esercizi --}}
                                        @foreach ($s->sessionExercises as $exercise)
                                            <div class="mb-3">
                                                <button wire:click="showExerciseHistory({{ $exercise->exercise_id }}, '{{ addslashes($exercise->exercise->name_it) }}')"
                                                        class="btn btn-link p-0 font-weight-bold mb-1 text-dark"
                                                        style="text-decoration:underline dotted;text-underline-offset:3px;">
                                                    {{ $exercise->exercise->name_it }}
                                                </button>
                                                <a href="{{ route('backoffice.exercises.show', $exercise->exercise) }}"
                                                   class="ml-1 text-muted" title="Scheda esercizio" target="_blank">
                                                    <i class="fas fa-external-link-alt fa-xs"></i>
                                                </a>
                                                <table class="table table-xs table-sm mb-0">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th style="width:40px">#</th>
                                                            <th>Pianificato</th>
                                                            <th></th>
                                                            <th>Eseguito</th>
                                                            <th>e1RM</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($exercise->sets->sortBy('set_index') as $set)
                                                            @php
                                                                $isWarmup = $set->is_warmup ?? false;
                                                                $isDone = $set->completed_at !== null;
                                                                $e1rm = $isDone
                                                                    ? \App\Services\E1rmCalculator::epley($set->actual_weight_kg, $set->actual_reps)
                                                                    : null;
                                                            @endphp
                                                            <tr class="{{ $isWarmup ? 'text-muted' : '' }}{{ !$isDone ? ' text-muted' : '' }}">
                                                                <td>
                                                                    {{ $set->set_index }}
                                                                    @if ($isWarmup)
                                                                        <small class="text-muted">(risc.)</small>
                                                                    @endif
                                                                </td>
                                                                <td class="text-muted small">
                                                                    {{ $set->planned_reps ?? '—' }}
                                                                    @if ($set->planned_weight_kg) × {{ $set->planned_weight_kg }} kg @endif
                                                                    @if ($set->planned_rir !== null) @ RIR{{ $set->planned_rir }} @endif
                                                                </td>
                                                                <td class="text-muted">→</td>
                                                                <td>
                                                                    @if ($isDone)
                                                                        {{ $set->actual_reps ?? '—' }}
                                                                        @if ($set->actual_weight_kg) × {{ $set->actual_weight_kg }} kg @endif
                                                                        @if ($set->actual_rir !== null) @ RIR{{ $set->actual_rir }} @endif
                                                                    @else
                                                                        <span class="text-muted">—</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if ($e1rm !== null)
                                                                        <span class="text-primary">{{ $e1rm }} kg</span>
                                                                    @else
                                                                        —
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Nessuna sessione completata.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($sessions->hasPages())
            <div class="card-footer">
                {{ $sessions->links() }}
            </div>
        @endif
    </div>

    {{-- Modal storico esercizio --}}
    @if ($exerciseHistoryId !== null)
        <div style="position:fixed;inset:0;z-index:1050;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;">
            <div class="card mb-0" style="width:640px;max-width:95vw;max-height:80vh;overflow-y:auto;">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">{{ $exerciseHistoryName }}</h5>
                    <button wire:click="showExerciseHistory({{ $exerciseHistoryId }}, '')"
                            class="btn btn-sm btn-secondary">&times;</button>
                </div>
                <div class="card-body p-0">
                    @forelse ($this->exerciseHistory as $se)
                        <div class="p-3 border-bottom">
                            <p class="text-primary font-weight-bold mb-2 small">
                                {{ $se->session->completed_at?->format('d/m/Y H:i') }} &mdash; {{ $se->session->name }}
                            </p>
                            <table class="table table-xs table-sm mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width:40px">#</th>
                                        <th>Reps</th>
                                        <th>Kg</th>
                                        <th>RIR</th>
                                        <th>e1RM</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($se->sets->sortBy('set_index')->whereNotNull('actual_reps') as $set)
                                        @php
                                            $e1rm = \App\Services\E1rmCalculator::epley($set->actual_weight_kg, $set->actual_reps);
                                        @endphp
                                        <tr>
                                            <td>{{ $set->set_index }}@if($set->is_warmup) <small class="text-muted">(risc.)</small>@endif</td>
                                            <td>{{ $set->actual_reps }}</td>
                                            <td>{{ $set->actual_weight_kg ?? '—' }}</td>
                                            <td>{{ $set->actual_rir ?? '—' }}</td>
                                            <td>{{ $e1rm !== null ? $e1rm.' kg' : '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @empty
                        <p class="text-muted text-center py-4">Nessuna sessione precedente.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
