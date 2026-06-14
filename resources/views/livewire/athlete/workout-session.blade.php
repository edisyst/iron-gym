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
