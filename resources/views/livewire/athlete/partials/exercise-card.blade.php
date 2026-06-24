{{--
  Partial per una card esercizio dentro la sessione workout.
  Variabili: $exercise (SessionExercise con sets e exercise caricati)
--}}
<div style="margin-bottom:{{ $loop->last ? '0' : '16px' }};">
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

    {{-- Header colonne set --}}
    <div style="display:grid;grid-template-columns:28px 1fr 70px 70px 60px 36px;gap:6px;align-items:center;
                font-size:10px;color:#555;font-weight:700;text-transform:uppercase;letter-spacing:.04em;
                padding:0 2px;margin-bottom:6px;">
        <span>#</span>
        <span>Obiettivo</span>
        <span style="text-align:center;">Reps</span>
        <span style="text-align:center;">Kg</span>
        <span style="text-align:center;">RIR</span>
        <span></span>
    </div>

    @foreach ($exercise->sets->sortBy('set_index') as $set)
        <div x-data="setTimer_{{ $set->id }}()"
             style="display:grid;grid-template-columns:28px 1fr 70px 70px 60px 36px;gap:6px;align-items:center;
                    padding:8px 2px;border-bottom:1px solid #2A2A2A;
                    {{ $set->completed_at ? 'opacity:.55;' : '' }}">

            {{-- Numero set --}}
            <span style="font-size:13px;color:#666;">{{ $set->set_index }}</span>

            {{-- Prescrizione --}}
            <span style="font-size:13px;color:#888;">
                @if ($set->planned_reps){{ $set->planned_reps }}r @endif
                @if ($set->planned_weight_kg){{ $set->planned_weight_kg }}kg @endif
                @if ($set->planned_rir !== null)RIR{{ $set->planned_rir }} @endif
            </span>

            {{-- Input reps --}}
            <input type="number" min="0"
                   wire:model="setData.{{ $set->id }}.reps"
                   class="workout-input"
                   placeholder="{{ $set->planned_reps ?? '—' }}"
                   @if ($set->completed_at) readonly @endif>

            {{-- Input peso --}}
            <input type="number" min="0" step="0.5"
                   wire:model="setData.{{ $set->id }}.weight"
                   class="workout-input"
                   placeholder="kg"
                   @if ($set->completed_at) readonly @endif>

            {{-- Input RIR --}}
            <input type="number" min="0" max="10"
                   wire:model="setData.{{ $set->id }}.rir"
                   class="workout-input"
                   placeholder="{{ $set->planned_rir ?? '—' }}"
                   @if ($set->completed_at) readonly @endif>

            {{-- Bottone completamento / icona check --}}
            @if ($set->completed_at)
                <div style="display:flex;align-items:center;justify-content:center;">
                    <svg style="width:22px;height:22px;color:#22c55e;" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
            @else
                <button
                    @click="
                        $wire.completeSet({{ $set->id }}).then(() => {
                            start({{ $exercise->planned_rest_sec ?? 90 }});
                        });
                    "
                    style="background:#FF6B00;border:none;border-radius:6px;width:32px;height:32px;
                           display:flex;align-items:center;justify-content:center;cursor:pointer;">
                    <svg style="width:16px;height:16px;color:#fff;" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </button>
            @endif
        </div>

        {{-- Countdown riposo (Alpine, client-side) --}}
        <div x-data="setTimer_{{ $set->id }}()"
             x-show="running"
             style="display:none;padding:8px 2px;background:#1A1A1A;border-radius:6px;margin-bottom:4px;
                    text-align:center;font-size:14px;color:#FF6B00;">
            Riposo: <span x-text="formatTime(seconds)"></span>
        </div>
    @endforeach
</div>

{{-- Definizione Alpine component per il timer del set --}}
@foreach ($exercise->sets as $set)
@if (!$set->completed_at)
<script>
function setTimer_{{ $set->id }}() {
    return {
        running: false,
        seconds: 0,
        intervalId: null,
        start(restSec) {
            this.seconds = restSec;
            this.running = true;
            if (this.intervalId) clearInterval(this.intervalId);
            this.intervalId = setInterval(() => {
                if (this.seconds <= 0) {
                    clearInterval(this.intervalId);
                    this.running = false;
                } else {
                    this.seconds--;
                }
            }, 1000);
        },
        formatTime(s) {
            const m = Math.floor(s / 60);
            const sec = s % 60;
            return m + ':' + String(sec).padStart(2, '0');
        }
    };
}
</script>
@endif
@endforeach
