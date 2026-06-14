<div>
        <h2 style="font-size:20px;font-weight:700;margin-bottom:16px;">Aggiungi misurazione</h2>

        @if (session()->has('saved'))
            <div class="athlete-card" style="background:#1a3a1a;color:#22c55e;margin-bottom:12px;">
                Misurazione salvata correttamente.
            </div>
        @endif

        <form wire:submit="save">
            <div class="athlete-card">
                <div style="margin-bottom:12px;">
                    <label style="font-size:12px;color:#888;display:block;margin-bottom:4px;">DATA</label>
                    <input type="date" class="workout-input" style="width:100%;text-align:left;"
                           wire:model="measuredAt">
                    @error('measuredAt') <span style="color:#ef4444;font-size:12px;">{{ $message }}</span> @enderror
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label style="font-size:12px;color:#888;display:block;margin-bottom:4px;">PESO (kg)</label>
                        <input type="number" step="0.1" class="workout-input" style="width:100%;text-align:left;"
                               wire:model="weightKg" placeholder="80.5">
                        @error('weightKg') <span style="color:#ef4444;font-size:12px;">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label style="font-size:12px;color:#888;display:block;margin-bottom:4px;">BODY FAT %</label>
                        <input type="number" step="0.1" class="workout-input" style="width:100%;text-align:left;"
                               wire:model="bodyFatPct" placeholder="15.0">
                        @error('bodyFatPct') <span style="color:#ef4444;font-size:12px;">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div style="margin-bottom:12px;">
                    <label style="font-size:12px;color:#888;display:block;margin-bottom:4px;">NOTE</label>
                    <textarea class="workout-input" style="width:100%;height:60px;text-align:left;resize:none;"
                              wire:model="notes"></textarea>
                </div>
            </div>

            {{-- Circonferenze in sezione collassabile --}}
            <details style="margin-bottom:16px;">
                <summary style="color:#FF6B00;font-size:14px;font-weight:600;cursor:pointer;padding:8px 0;">
                    Circonferenze (opzionale)
                </summary>
                <div class="athlete-card" style="margin-top:8px;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div>
                            <label style="font-size:12px;color:#888;display:block;margin-bottom:4px;">PETTO (cm)</label>
                            <input type="number" step="0.1" class="workout-input" style="width:100%;text-align:left;"
                                   wire:model="chestCm">
                        </div>
                        <div>
                            <label style="font-size:12px;color:#888;display:block;margin-bottom:4px;">VITA (cm)</label>
                            <input type="number" step="0.1" class="workout-input" style="width:100%;text-align:left;"
                                   wire:model="waistCm">
                        </div>
                        <div>
                            <label style="font-size:12px;color:#888;display:block;margin-bottom:4px;">FIANCHI (cm)</label>
                            <input type="number" step="0.1" class="workout-input" style="width:100%;text-align:left;"
                                   wire:model="hipsCm">
                        </div>
                        <div>
                            <label style="font-size:12px;color:#888;display:block;margin-bottom:4px;">BRACCIO SX</label>
                            <input type="number" step="0.1" class="workout-input" style="width:100%;text-align:left;"
                                   wire:model="leftArmCm">
                        </div>
                        <div>
                            <label style="font-size:12px;color:#888;display:block;margin-bottom:4px;">BRACCIO DX</label>
                            <input type="number" step="0.1" class="workout-input" style="width:100%;text-align:left;"
                                   wire:model="rightArmCm">
                        </div>
                        <div>
                            <label style="font-size:12px;color:#888;display:block;margin-bottom:4px;">COSCIA SX</label>
                            <input type="number" step="0.1" class="workout-input" style="width:100%;text-align:left;"
                                   wire:model="leftThighCm">
                        </div>
                        <div>
                            <label style="font-size:12px;color:#888;display:block;margin-bottom:4px;">COSCIA DX</label>
                            <input type="number" step="0.1" class="workout-input" style="width:100%;text-align:left;"
                                   wire:model="rightThighCm">
                        </div>
                        <div>
                            <label style="font-size:12px;color:#888;display:block;margin-bottom:4px;">POLPACCIO SX</label>
                            <input type="number" step="0.1" class="workout-input" style="width:100%;text-align:left;"
                                   wire:model="leftCalfCm">
                        </div>
                        <div>
                            <label style="font-size:12px;color:#888;display:block;margin-bottom:4px;">POLPACCIO DX</label>
                            <input type="number" step="0.1" class="workout-input" style="width:100%;text-align:left;"
                                   wire:model="rightCalfCm">
                        </div>
                    </div>
                </div>
            </details>

            <button type="submit" class="btn-accent" wire:loading.attr="disabled">
                <span wire:loading wire:target="save">Salvataggio...</span>
                <span wire:loading.remove wire:target="save">Salva misurazione</span>
            </button>
        </form>

        {{-- Storico ultime 5 misurazioni --}}
        <div style="margin-top:24px;">
            <p class="section-title">Ultime misurazioni</p>
            @forelse ($recentMeasurements as $m)
                <div class="athlete-card" style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;">
                    <span style="font-size:14px;color:#ccc;">{{ $m->measured_at->format('d/m/Y') }}</span>
                    <span style="font-size:16px;font-weight:700;color:#fff;">
                        {{ $m->weight_kg !== null ? number_format((float)$m->weight_kg, 1).' kg' : '—' }}
                    </span>
                    <span style="font-size:13px;color:#888;">
                        {{ $m->body_fat_pct !== null ? number_format((float)$m->body_fat_pct, 1).'%' : '—' }}
                    </span>
                </div>
            @empty
                <div class="athlete-card" style="color:#888;text-align:center;">
                    Nessuna misurazione registrata
                </div>
            @endforelse
        </div>
</div>
