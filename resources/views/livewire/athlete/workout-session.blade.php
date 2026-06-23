<div>
    {{-- Header sessione --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <div>
            <p style="font-size:12px;color:#666;margin-bottom:2px;">
                Settimana {{ $session->week->week_number }}
            </p>
            <h1 style="font-size:22px;font-weight:700;">{{ $session->name }}</h1>
        </div>
        <button wire:click="skipSession"
                wire:confirm="Sei sicuro di voler saltare questa sessione?"
                style="background:transparent;border:none;color:#666;font-size:13px;cursor:pointer;padding:8px;">
            Salta
        </button>
    </div>

    {{-- Lista esercizi --}}
    @php
        // Raggruppa gli esercizi: group_id null = standalone, altrimenti per gruppo
        $grouped = $session->sessionExercises->groupBy(fn ($e) => $e->group_id ?? 'solo_' . $e->id);
    @endphp

    @foreach ($grouped as $groupKey => $exercises)
        @if ($exercises->first()->group_id !== null && $exercises->first()->group !== null)
            {{-- CARD GRUPPO (superset / giant set) --}}
            <div class="athlete-card" style="border-left: 3px solid #FF6B00;">
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;color:#FF6B00;letter-spacing:.06em;margin-bottom:12px;">
                    {{ $exercises->first()->group->group_type === 'superset' ? 'Superset' : 'Giant set' }}
                    &bull; {{ $exercises->first()->group->rounds }} round
                </p>

                @foreach ($exercises->sortBy('order_in_group') as $exercise)
                    @include('livewire.athlete.partials.exercise-card', ['exercise' => $exercise])
                @endforeach
            </div>
        @else
            {{-- CARD ESERCIZIO STANDALONE --}}
            @php $exercise = $exercises->first(); @endphp
            <div class="athlete-card">
                @include('livewire.athlete.partials.exercise-card', ['exercise' => $exercise])
            </div>
        @endif
    @endforeach

    {{-- Bottone completa sessione --}}
    @if ($this->canCompleteSession())
        <div style="margin-top:8px;margin-bottom:24px;">
            <button wire:click="completeSession" class="btn-accent"
                    wire:loading.attr="disabled">
                <span wire:loading.remove>Completa sessione</span>
                <span wire:loading>Salvataggio...</span>
            </button>
        </div>
    @endif

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

    {{-- Form feedback (a:open-feedback => x-show) --}}
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
