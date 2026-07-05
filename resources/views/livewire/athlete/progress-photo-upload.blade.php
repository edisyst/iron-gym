<div>
    <h2 style="font-size:20px;font-weight:700;margin-bottom:16px;">Foto progressi</h2>

    @if ($this->saved)
        <div class="athlete-card" style="background:#1a3a1a;color:#22c55e;margin-bottom:12px;">
            Foto salvate correttamente.
        </div>
    @endif

    <form wire:submit="save">
        <div class="athlete-card">
            <div style="margin-bottom:16px;">
                <label style="font-size:12px;color:#888;display:block;margin-bottom:4px;">DATA FOTO</label>
                <input type="date" class="workout-input" style="width:100%;text-align:left;"
                       wire:model.live="takenAt">
                @error('takenAt') <span class="ig-field-error">{{ $message }}</span> @enderror
            </div>

            @php
                $poseLabels = [
                    'front' => 'Fronte',
                    'back' => 'Retro',
                    'side_left' => 'Fianco SX',
                    'side_right' => 'Fianco DX',
                ];
            @endphp

            @foreach ($poseLabels as $pose => $label)
                <div style="margin-bottom:16px;">
                    <label style="font-size:12px;color:#888;display:block;margin-bottom:6px;">{{ strtoupper($label) }}</label>

                    {{-- Anteprima foto già caricata per questa data --}}
                    @if ($uploaded->has($pose))
                        <div style="margin-bottom:8px;">
                            <img src="{{ $uploaded->get($pose)->url }}"
                                 style="width:80px;height:100px;object-fit:cover;border-radius:8px;border:2px solid #FF6B00;"
                                 alt="{{ $label }}"
                                 onerror="this.style.display='none'">
                            <span style="font-size:11px;color:#22c55e;display:block;margin-top:4px;">Caricata</span>
                        </div>
                    @endif

                    <input type="file" accept="image/jpeg,image/jpg,image/png"
                           wire:model="photos.{{ $pose }}"
                           style="color:#ccc;font-size:13px;">
                    @error("photos.$pose") <span class="ig-field-error">{{ $message }}</span> @enderror

                    {{-- Progress upload --}}
                    <div wire:loading wire:target="photos.{{ $pose }}"
                         style="font-size:11px;color:#888;margin-top:4px;">Caricamento...</div>
                </div>
            @endforeach

            <div style="margin-bottom:16px;">
                <label style="font-size:12px;color:#888;display:block;margin-bottom:4px;">NOTE</label>
                <textarea class="workout-input" style="width:100%;height:60px;text-align:left;resize:none;"
                          wire:model="notes" placeholder="Opzionale"></textarea>
            </div>
        </div>

        <button type="submit" class="btn-accent" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading wire:target="save">Salvataggio...</span>
            <span wire:loading.remove wire:target="save">Salva foto</span>
        </button>
    </form>
</div>
