<div>
    {{-- Header --}}
    <div style="margin-bottom: 16px;">
        <h2 style="font-size: 20px; font-weight: 700; color: #fff; margin-bottom: 4px;">Catalogo Esercizi</h2>
        <p style="font-size: 13px; color: #888;">{{ $exercises->total() }} esercizi disponibili</p>
    </div>

    {{-- Filtri --}}
    <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px;">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Cerca esercizio..."
            style="background:#2A2A2A; border:1px solid #333; border-radius:8px; color:#fff; padding:10px 14px; font-size:14px; width:100%;"
        >
        <div style="display: flex; gap: 8px;">
            <select wire:model.live="mechanic"
                    style="flex:1; background:#2A2A2A; border:1px solid #333; border-radius:8px; color:#888; padding:8px 12px; font-size:13px;">
                <option value="">Tutte le meccaniche</option>
                <option value="compound">Multi-articolare</option>
                <option value="isolation">Mono-articolare</option>
            </select>
            <select wire:model.live="muscleGroup"
                    style="flex:1; background:#2A2A2A; border:1px solid #333; border-radius:8px; color:#888; padding:8px 12px; font-size:13px;">
                <option value="">Tutti i muscoli</option>
                <option value="chest">Petto</option>
                <option value="back">Schiena</option>
                <option value="shoulders">Spalle</option>
                <option value="arms">Braccia</option>
                <option value="legs">Gambe</option>
                <option value="core">Core</option>
            </select>
        </div>
    </div>

    {{-- Lista esercizi --}}
    <div wire:loading.class="opacity-50">
        @forelse ($exercises as $exercise)
            @php
                $primaryMuscle = $exercise->muscles
                    ->filter(fn ($m) => $m->pivot->role === 'primary')
                    ->sortByDesc(fn ($m) => $m->pivot->contribution_pct)
                    ->first();
                $imgUrl = null;
                foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
                    if (file_exists(public_path("images/exercises/{$exercise->slug}.{$ext}"))) {
                        $imgUrl = asset("images/exercises/{$exercise->slug}.{$ext}");
                        break;
                    }
                }
            @endphp
            <a href="{{ route('athlete.exercises.show', $exercise) }}"
               style="display:flex; align-items:center; gap:12px; background:#1E1E1E; border-radius:12px; padding:12px 14px; margin-bottom:10px; text-decoration:none; transition: background 0.15s;"
               onmouseover="this.style.background='#252525'" onmouseout="this.style.background='#1E1E1E'">

                {{-- Thumb / iniziale --}}
                <div style="width:44px; height:44px; border-radius:8px; background:#2A2A2A; flex-shrink:0; overflow:hidden; display:flex; align-items:center; justify-content:center;">
                    @if ($imgUrl)
                        <img src="{{ $imgUrl }}" alt="{{ $exercise->name_it }}" style="width:100%; height:100%; object-fit:cover;">
                    @else
                        <span style="font-size:18px; font-weight:700; color:#555;">{{ mb_substr($exercise->name_it, 0, 1) }}</span>
                    @endif
                </div>

                {{-- Info --}}
                <div style="flex:1; min-width:0;">
                    <div style="font-size:15px; font-weight:600; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $exercise->name_it }}</div>
                    <div style="font-size:12px; color:#888; margin-top:2px;">
                        @if ($primaryMuscle)
                            {{ $primaryMuscle->name_it }}
                        @endif
                        <span style="color:#444; margin:0 4px;">·</span>
                        @if ($exercise->mechanic === 'compound')
                            <span style="color:#FF6B00; font-size:11px; font-weight:600;">COMPOUND</span>
                        @else
                            <span style="color:#888; font-size:11px; font-weight:600;">ISOLATION</span>
                        @endif
                    </div>
                </div>

                {{-- Livello --}}
                <div style="flex-shrink:0;">
                    @if ($exercise->skill_level === 'beginner')
                        <span class="athlete-badge badge-green" style="font-size:10px;">Base</span>
                    @elseif ($exercise->skill_level === 'intermediate')
                        <span class="athlete-badge badge-accent" style="font-size:10px;">Inter.</span>
                    @else
                        <span class="athlete-badge badge-red" style="font-size:10px;">Avanz.</span>
                    @endif
                </div>

                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#444" stroke-width="2" style="flex-shrink:0;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        @empty
            <div style="text-align:center; color:#555; padding:40px 0;">
                <p style="font-size:15px;">Nessun esercizio trovato</p>
                <p style="font-size:13px; margin-top:4px;">Prova a cambiare i filtri</p>
            </div>
        @endforelse
    </div>

    {{-- Paginazione --}}
    @if ($exercises->hasPages())
        <div style="display:flex; justify-content:center; align-items:center; gap:12px; margin-top:16px; padding:8px 0;">
            @if ($exercises->onFirstPage())
                <span style="color:#444; font-size:13px;">← Prec.</span>
            @else
                <button wire:click="previousPage" style="background:none; border:1px solid #333; border-radius:8px; color:#888; padding:8px 16px; font-size:13px; cursor:pointer;">← Prec.</button>
            @endif

            <span style="color:#888; font-size:13px;">{{ $exercises->currentPage() }} / {{ $exercises->lastPage() }}</span>

            @if ($exercises->hasMorePages())
                <button wire:click="nextPage" style="background:none; border:1px solid #333; border-radius:8px; color:#888; padding:8px 16px; font-size:13px; cursor:pointer;">Succ. →</button>
            @else
                <span style="color:#444; font-size:13px;">Succ. →</span>
            @endif
        </div>
    @endif
</div>
