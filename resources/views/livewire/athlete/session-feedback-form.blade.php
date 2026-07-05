<div>
    <h2 style="font-size:18px;font-weight:700;margin-bottom:4px;">Come è andata?</h2>
    <p style="font-size:13px;color:#888;margin-bottom:20px;">
        Valuta ogni aspetto da 0 (pessimo) a 3 (ottimo).
    </p>

    @php
        $metrics = [
            ['model' => 'pump',            'label' => 'Pump muscolare'],
            ['model' => 'sorenessPrev',    'label' => 'Dolori dalla sessione prec.'],
            ['model' => 'perceivedEffort', 'label' => 'Sforzo percepito'],
            ['model' => 'jointPain',       'label' => 'Dolore articolare'],
            ['model' => 'performance',     'label' => 'Performance'],
        ];
    @endphp

    @foreach ($metrics as $metric)
        <div class="metric-row">
            <span class="metric-label">{{ $metric['label'] }}</span>
            <div class="metric-options">
                @for ($v = 0; $v <= 3; $v++)
                    <label>
                        <input type="radio"
                               wire:model="{{ $metric['model'] }}"
                               value="{{ $v }}">
                        <span style="display:flex;align-items:center;justify-content:center;
                                     min-height:48px;border-radius:var(--ig-radius);
                                     border:1px solid {{ (int)($this->{$metric['model']}) === $v ? '#FF6B00' : '#333' }};
                                     background:{{ (int)($this->{$metric['model']}) === $v ? '#FF6B00' : 'transparent' }};
                                     color:{{ (int)($this->{$metric['model']}) === $v ? '#fff' : '#aaa' }};
                                     cursor:pointer;font-size:var(--ig-text-base);font-weight:600;">
                            {{ $v }}
                        </span>
                    </label>
                @endfor
            </div>
        </div>
    @endforeach

    {{-- Ore di sonno --}}
    <div style="margin-bottom:16px;">
        <label style="color:#ccc;font-size:14px;display:block;margin-bottom:6px;">
            Ore di sonno (opzionale)
        </label>
        <input type="number" min="0" max="24" step="0.5"
               wire:model="sleepHours"
               class="workout-input"
               style="width:100px;"
               placeholder="es. 7.5">
    </div>

    {{-- Stress --}}
    <div class="metric-row">
        <span class="metric-label">Stress</span>
        <div class="metric-options">
            @for ($v = 0; $v <= 3; $v++)
                <label>
                    <input type="radio" wire:model="stressLevel" value="{{ $v }}">
                    <span style="display:flex;align-items:center;justify-content:center;
                                 min-height:48px;border-radius:var(--ig-radius);
                                 border:1px solid {{ (int)$stressLevel === $v ? '#FF6B00' : '#333' }};
                                 background:{{ (int)$stressLevel === $v ? '#FF6B00' : 'transparent' }};
                                 color:{{ (int)$stressLevel === $v ? '#fff' : '#aaa' }};
                                 cursor:pointer;font-size:var(--ig-text-base);font-weight:600;">
                        {{ $v }}
                    </span>
                </label>
            @endfor
        </div>
    </div>

    {{-- Note libere --}}
    <div style="margin-bottom:20px;">
        <label style="color:#ccc;font-size:14px;display:block;margin-bottom:6px;">
            Note libere (opzionale)
        </label>
        <textarea wire:model="note" rows="3"
                  style="background:#2A2A2A;border:1px solid #333;border-radius:8px;
                         color:#fff;padding:10px;width:100%;font-size:14px;resize:vertical;"
                  placeholder="Come ti sei sentito?"></textarea>
    </div>

    {{-- Azioni --}}
    <div style="display:flex;gap:12px;">
        <button wire:click="save" class="btn-accent" style="flex:1;"
                wire:loading.attr="disabled">
            <span wire:loading.remove>Salva feedback</span>
            <span wire:loading>Salvataggio...</span>
        </button>
        <button wire:click="skip" class="btn-ghost">
            Salta
        </button>
    </div>
</div>
