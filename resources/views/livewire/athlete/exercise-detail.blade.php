<div>
    {{-- Back button --}}
    <div style="margin-bottom: 16px;">
        <a href="{{ route('athlete.exercises.index') }}"
           style="display:inline-flex; align-items:center; gap:6px; color:#888; font-size:14px; text-decoration:none;">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Catalogo
        </a>
    </div>

    {{-- Header esercizio --}}
    <div style="margin-bottom: 20px;">
        <h1 style="font-size: 22px; font-weight: 700; color: #fff; margin-bottom: 6px;">{{ $exercise->name_it }}</h1>
        <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            @if ($exercise->mechanic === 'compound')
                <span class="athlete-badge badge-accent">Compound</span>
            @else
                <span class="athlete-badge badge-gray">Isolation</span>
            @endif
            @if ($exercise->skill_level === 'beginner')
                <span class="athlete-badge badge-green">Principiante</span>
            @elseif ($exercise->skill_level === 'intermediate')
                <span class="athlete-badge badge-accent">Intermedio</span>
            @else
                <span class="athlete-badge badge-red">Avanzato</span>
            @endif
        </div>
    </div>

    {{-- Immagine --}}
    @php
        $imgUrl = null;
        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
            if (file_exists(public_path("images/exercises/{$exercise->slug}.{$ext}"))) {
                $imgUrl = asset("images/exercises/{$exercise->slug}.{$ext}");
                break;
            }
        }
    @endphp
    @if ($imgUrl)
        <div class="athlete-card" style="padding:0; overflow:hidden; text-align:center; background:#111;">
            <img src="{{ $imgUrl }}" alt="{{ $exercise->name_it }}"
                 style="max-width:100%; max-height:240px; object-fit:contain;">
        </div>
    @endif

    {{-- Video --}}
    @if ($exercise->video_url)
        <a href="{{ $exercise->video_url }}" target="_blank" rel="noopener noreferrer"
           style="display:flex; align-items:center; gap:10px; background:#1E1E1E; border-radius:12px; padding:14px 16px; margin-bottom:12px; text-decoration:none; color:#FF6B00; font-size:14px; font-weight:600;">
            <svg width="22" height="22" fill="#FF6B00" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7L8 5z"/>
            </svg>
            Guarda il video tecnico
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#FF6B00" stroke-width="2" style="margin-left:auto;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
        </a>
    @endif

    {{-- Classificazione --}}
    <div class="athlete-card">
        <div class="section-title" style="margin-bottom:12px;">Classificazione</div>
        @php
            $pattern    = $exercise->compoundPattern ?? $exercise->jointAction;
            $isCompound = $exercise->compoundPattern !== null;
            $planeLabel = match($exercise->plane) {
                'sagittal'    => 'Sagittale',
                'frontal'     => 'Frontale',
                'transverse'  => 'Trasversale',
                'multiplanar' => 'Multipiano',
                default       => ucfirst($exercise->plane),
            };
            $lateralityLabel = match($exercise->laterality) {
                'bilateral'              => 'Bilaterale',
                'unilateral_alternating' => 'Unilaterale alternato',
                'unilateral_isolated'    => 'Unilaterale isolato',
                default                  => str_replace('_', ' ', $exercise->laterality),
            };
        @endphp
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div>
                <div style="font-size:11px; color:#555; text-transform:uppercase; letter-spacing:.05em; margin-bottom:3px;">Piano</div>
                <div style="font-size:14px; color:#ccc;">{{ $planeLabel }}</div>
            </div>
            <div>
                <div style="font-size:11px; color:#555; text-transform:uppercase; letter-spacing:.05em; margin-bottom:3px;">Lateralità</div>
                <div style="font-size:14px; color:#ccc;">{{ $lateralityLabel }}</div>
            </div>
            @if ($pattern)
                <div style="grid-column: span 2;">
                    <div style="font-size:11px; color:#555; text-transform:uppercase; letter-spacing:.05em; margin-bottom:3px;">Pattern motorio</div>
                    <div style="font-size:14px; color:#ccc;">
                        {{ $pattern->name_it }}
                        <span style="font-size:11px; color:#555; margin-left:4px;">{{ $isCompound ? '(compound)' : '(joint action)' }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Attrezzatura --}}
    @if ($exercise->equipment->count())
        <div class="athlete-card">
            <div class="section-title" style="margin-bottom:10px;">Attrezzatura</div>
            <div style="display:flex; flex-wrap:wrap; gap:8px;">
                @foreach ($exercise->equipment as $eq)
                    <span style="background:#2A2A2A; border:1px solid #333; border-radius:20px; padding:4px 12px; font-size:13px; color:#ccc;">{{ $eq->name_it }}</span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Muscoli coinvolti --}}
    @if ($exercise->muscles->count())
        @php
            $roleOrder = ['primary' => 0, 'secondary' => 1, 'stabilizer' => 2];
            $sortedMuscles = $exercise->muscles->sortBy([
                fn ($a, $b) => ($roleOrder[$a->pivot->role] ?? 9) <=> ($roleOrder[$b->pivot->role] ?? 9),
                fn ($a, $b) => $b->pivot->contribution_pct <=> $a->pivot->contribution_pct,
            ]);
        @endphp
        <div class="athlete-card">
            <div class="section-title" style="margin-bottom:12px;">Muscoli coinvolti</div>
            <div style="display:flex; flex-direction:column; gap:10px;">
                @foreach ($sortedMuscles as $muscle)
                    @php
                        $roleLabel = match($muscle->pivot->role) {
                            'primary'    => 'Primario',
                            'secondary'  => 'Secondario',
                            'stabilizer' => 'Stabilizzatore',
                            default      => ucfirst($muscle->pivot->role),
                        };
                        $barColor = match($muscle->pivot->role) {
                            'primary'    => '#FF6B00',
                            'secondary'  => '#facc15',
                            'stabilizer' => '#38bdf8',
                            default      => '#555',
                        };
                    @endphp
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                            <span style="font-size:14px; color:#ccc; font-weight:500;">{{ $muscle->name_it }}</span>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="font-size:11px; color:#666;">{{ $roleLabel }}</span>
                                <span style="font-size:12px; color:#888;">{{ $muscle->pivot->contribution_pct }}%</span>
                            </div>
                        </div>
                        <div style="background:#2A2A2A; border-radius:4px; height:6px; overflow:hidden;">
                            <div style="width:{{ $muscle->pivot->contribution_pct }}%; background:{{ $barColor }}; height:100%; border-radius:4px;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Descrizione esecuzione --}}
    @if ($exercise->execution_description || $exercise->description)
        <div class="athlete-card">
            <div class="section-title" style="margin-bottom:10px;">Come eseguirlo</div>
            <p style="font-size:14px; color:#ccc; line-height:1.6; white-space:pre-line; margin:0;">{{ $exercise->execution_description ?? $exercise->description }}</p>
        </div>
    @endif
</div>
