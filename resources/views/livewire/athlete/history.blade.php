<div>
    <h1 style="font-size:20px;font-weight:700;margin-bottom:16px;">Storico</h1>

    {{-- Filtro mesociclo --}}
    <div class="athlete-card" style="padding:12px 14px;margin-bottom:16px;">
        <select wire:model.live="mesocycleId"
                style="background:#2A2A2A;border:1px solid #333;border-radius:6px;
                       color:#fff;padding:8px 10px;width:100%;font-size:14px;">
            <option value="">Tutti i mesocicli</option>
            @foreach ($mesocycles as $meso)
                <option value="{{ $meso->id }}">{{ $meso->name }}</option>
            @endforeach
        </select>
    </div>

    @forelse ($sessions as $session)
        {{-- Card sessione --}}
        <div class="athlete-card" style="margin-bottom:12px;">
            <div wire:click="showDetail({{ $session->id }})"
                 style="cursor:pointer;display:flex;align-items:center;gap:12px;">
                <div style="flex:1;min-width:0;">
                    <p style="font-size:15px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        {{ $session->name }}
                    </p>
                    <p style="font-size:12px;color:#666;margin-top:3px;">
                        {{ $session->completed_at?->format('d/m/Y') }}
                        @php $dur = $this->duration($session); @endphp
                        @if ($dur) &bull; {{ $dur }} @endif
                        &bull; {{ $session->week->mesocycle->name }}
                        &bull; {{ $this->completedSetsCount($session) }} set
                    </p>
                </div>
                <svg style="width:18px;height:18px;color:#555;flex-shrink:0;
                     transition:transform .2s;{{ $selectedSessionId === $session->id ? 'transform:rotate(90deg)' : '' }}"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </div>

            {{-- Pannello dettaglio --}}
            @if ($selectedSessionId === $session->id && $this->selectedSession !== null)
                <div style="margin-top:16px;border-top:1px solid #2A2A2A;padding-top:16px;">
                    @foreach ($this->selectedSession->sessionExercises as $exercise)
                        <div style="margin-bottom:16px;">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                                <button wire:click="showExerciseHistory({{ $exercise->exercise_id }}, '{{ addslashes($exercise->exercise->name_it) }}')"
                                        style="font-size:14px;font-weight:600;color:#ccc;flex:1;
                                               background:none;border:none;padding:0;text-align:left;cursor:pointer;
                                               text-decoration:underline dotted;text-underline-offset:3px;">
                                    {{ $exercise->exercise->name_it }}
                                </button>
                                <button wire:click="showExerciseDetail({{ $exercise->exercise_id }})"
                                        title="Dettagli esercizio"
                                        style="background:#2A2A2A;border:1px solid #3A3A3A;border-radius:8px;padding:4px 10px;
                                               font-size:11px;font-weight:600;color:#aaa;cursor:pointer;white-space:nowrap;
                                               display:flex;align-items:center;gap:4px;line-height:1.4;">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16v-4M12 8h.01"/>
                                    </svg>
                                    Info
                                </button>
                            </div>

                            @foreach ($exercise->sets->sortBy('set_index')->whereNotNull('actual_reps') as $set)
                                <div style="display:flex;gap:12px;font-size:13px;color:#888;
                                            padding:4px 0;border-bottom:1px solid #222;">
                                    <span style="color:#666;width:24px;">{{ $set->set_index }}</span>
                                    <span>{{ $set->actual_reps }} reps</span>
                                    @if ($set->actual_weight_kg)
                                        <span>{{ $set->actual_weight_kg }} kg</span>
                                    @endif
                                    @if ($set->actual_rir !== null)
                                        <span>RIR {{ $set->actual_rir }}</span>
                                    @endif
                                    @if ($set->estimated_1rm)
                                        <span style="color:#FF6B00;margin-left:auto;">
                                            e1RM {{ $set->estimated_1rm }} kg
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <div class="athlete-card" style="text-align:center;padding:32px 16px;">
            <p style="color:#666;">Nessuna sessione completata.</p>
        </div>
    @endforelse

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

    {{-- Drawer dettaglio esercizio --}}
    @if ($exerciseDetailId !== null && $this->exerciseDetail !== null)
        @php $ex = $this->exerciseDetail; @endphp
        <div style="position:fixed;inset:0;z-index:400;background:rgba(0,0,0,.85);display:flex;align-items:flex-end;">
            <div style="background:#1E1E1E;border-radius:16px 16px 0 0;padding:20px 16px 32px;width:100%;
                        max-height:88vh;overflow-y:auto;">

                {{-- Handle + header --}}
                <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;">
                    <div style="flex:1;">
                        <h2 style="font-size:18px;font-weight:700;color:#fff;margin:0 0 6px;">{{ $ex->name_it }}</h2>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            @if ($ex->mechanic === 'compound')
                                <span style="font-size:10px;background:#FF6B00;color:#fff;padding:2px 8px;border-radius:999px;font-weight:700;">Compound</span>
                            @else
                                <span style="font-size:10px;background:#2A2A2A;color:#aaa;padding:2px 8px;border-radius:999px;font-weight:700;">Isolation</span>
                            @endif
                            @if ($ex->skill_level === 'beginner')
                                <span style="font-size:10px;background:#166534;color:#bbf7d0;padding:2px 8px;border-radius:999px;font-weight:700;">Principiante</span>
                            @elseif ($ex->skill_level === 'intermediate')
                                <span style="font-size:10px;background:#FF6B00;color:#fff;padding:2px 8px;border-radius:999px;font-weight:700;">Intermedio</span>
                            @else
                                <span style="font-size:10px;background:#7f1d1d;color:#fca5a5;padding:2px 8px;border-radius:999px;font-weight:700;">Avanzato</span>
                            @endif
                        </div>
                    </div>
                    <button wire:click="showExerciseDetail({{ $exerciseDetailId }})"
                            style="background:none;border:none;color:#666;font-size:26px;line-height:1;cursor:pointer;padding:0 0 0 12px;">&times;</button>
                </div>

                {{-- Video --}}
                @if ($ex->video_url)
                    <a href="{{ $ex->video_url }}" target="_blank" rel="noopener noreferrer"
                       style="display:flex;align-items:center;gap:10px;background:#2A2A2A;border-radius:10px;
                              padding:12px 14px;margin-bottom:14px;text-decoration:none;color:#FF6B00;font-size:13px;font-weight:600;">
                        <svg width="18" height="18" fill="#FF6B00" viewBox="0 0 24 24"><path d="M8 5v14l11-7L8 5z"/></svg>
                        Guarda il video tecnico
                        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="#FF6B00" stroke-width="2" style="margin-left:auto;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </a>
                @endif

                {{-- Classificazione --}}
                @php
                    $pattern    = $ex->compoundPattern ?? $ex->jointAction;
                    $isCompound = $ex->compoundPattern !== null;
                    $planeLabel = match($ex->plane) {
                        'sagittal'    => 'Sagittale',
                        'frontal'     => 'Frontale',
                        'transverse'  => 'Trasversale',
                        'multiplanar' => 'Multipiano',
                        default       => ucfirst($ex->plane ?? ''),
                    };
                    $lateralityLabel = match($ex->laterality) {
                        'bilateral'              => 'Bilaterale',
                        'unilateral_alternating' => 'Unilaterale alternato',
                        'unilateral_isolated'    => 'Unilaterale isolato',
                        default                  => str_replace('_', ' ', $ex->laterality ?? ''),
                    };
                @endphp
                <div style="background:#262626;border-radius:10px;padding:14px;margin-bottom:14px;">
                    <p style="font-size:10px;color:#555;text-transform:uppercase;letter-spacing:.05em;font-weight:700;margin-bottom:10px;">Classificazione</p>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <div>
                            <div style="font-size:10px;color:#555;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;">Piano</div>
                            <div style="font-size:13px;color:#ccc;">{{ $planeLabel }}</div>
                        </div>
                        <div>
                            <div style="font-size:10px;color:#555;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;">Lateralità</div>
                            <div style="font-size:13px;color:#ccc;">{{ $lateralityLabel }}</div>
                        </div>
                        @if ($pattern)
                            <div style="grid-column:span 2;">
                                <div style="font-size:10px;color:#555;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;">Pattern motorio</div>
                                <div style="font-size:13px;color:#ccc;">
                                    {{ $pattern->name_it }}
                                    <span style="font-size:10px;color:#555;margin-left:4px;">{{ $isCompound ? '(compound)' : '(joint action)' }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Attrezzatura --}}
                @if ($ex->equipment->count())
                    <div style="background:#262626;border-radius:10px;padding:14px;margin-bottom:14px;">
                        <p style="font-size:10px;color:#555;text-transform:uppercase;letter-spacing:.05em;font-weight:700;margin-bottom:10px;">Attrezzatura</p>
                        <div style="display:flex;flex-wrap:wrap;gap:6px;">
                            @foreach ($ex->equipment as $eq)
                                <span style="background:#1E1E1E;border:1px solid #333;border-radius:20px;padding:3px 10px;font-size:12px;color:#ccc;">{{ $eq->name_it }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Muscoli coinvolti --}}
                @if ($ex->muscles->count())
                    @php
                        $roleOrder = ['primary' => 0, 'secondary' => 1, 'stabilizer' => 2];
                        $sortedMuscles = $ex->muscles->sortBy([
                            fn ($a, $b) => ($roleOrder[$a->pivot->role] ?? 9) <=> ($roleOrder[$b->pivot->role] ?? 9),
                            fn ($a, $b) => $b->pivot->contribution_pct <=> $a->pivot->contribution_pct,
                        ]);
                    @endphp
                    <div style="background:#262626;border-radius:10px;padding:14px;margin-bottom:14px;">
                        <p style="font-size:10px;color:#555;text-transform:uppercase;letter-spacing:.05em;font-weight:700;margin-bottom:10px;">Muscoli coinvolti</p>
                        <div style="display:flex;flex-direction:column;gap:8px;">
                            @foreach ($sortedMuscles as $muscle)
                                @php
                                    $barColor = match($muscle->pivot->role) {
                                        'primary'    => '#FF6B00',
                                        'secondary'  => '#facc15',
                                        'stabilizer' => '#38bdf8',
                                        default      => '#555',
                                    };
                                    $roleLabel = match($muscle->pivot->role) {
                                        'primary'    => 'Primario',
                                        'secondary'  => 'Secondario',
                                        'stabilizer' => 'Stabilizzatore',
                                        default      => ucfirst($muscle->pivot->role),
                                    };
                                @endphp
                                <div>
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px;">
                                        <span style="font-size:13px;color:#ccc;font-weight:500;">{{ $muscle->name_it }}</span>
                                        <div style="display:flex;align-items:center;gap:6px;">
                                            <span style="font-size:10px;color:#666;">{{ $roleLabel }}</span>
                                            <span style="font-size:11px;color:#888;">{{ $muscle->pivot->contribution_pct }}%</span>
                                        </div>
                                    </div>
                                    <div style="background:#1A1A1A;border-radius:4px;height:5px;overflow:hidden;">
                                        <div style="width:{{ $muscle->pivot->contribution_pct }}%;background:{{ $barColor }};height:100%;border-radius:4px;"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Esecuzione --}}
                @if ($ex->execution_description || $ex->description)
                    <div style="background:#262626;border-radius:10px;padding:14px;">
                        <p style="font-size:10px;color:#555;text-transform:uppercase;letter-spacing:.05em;font-weight:700;margin-bottom:8px;">Come eseguirlo</p>
                        <p style="font-size:13px;color:#ccc;line-height:1.6;white-space:pre-line;margin:0;">{{ $ex->execution_description ?? $ex->description }}</p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Paginazione --}}
    @if ($sessions->hasPages())
        <div style="margin-top:16px;display:flex;gap:8px;justify-content:center;">
            @if ($sessions->onFirstPage())
                <span style="color:#444;padding:8px 14px;border:1px solid #333;border-radius:6px;">&#8249;</span>
            @else
                <button wire:click="previousPage" style="background:#2A2A2A;color:#fff;border:1px solid #333;
                        border-radius:6px;padding:8px 14px;cursor:pointer;">&#8249;</button>
            @endif
            <span style="color:#888;padding:8px 14px;">{{ $sessions->currentPage() }} / {{ $sessions->lastPage() }}</span>
            @if ($sessions->hasMorePages())
                <button wire:click="nextPage" style="background:#2A2A2A;color:#fff;border:1px solid #333;
                        border-radius:6px;padding:8px 14px;cursor:pointer;">&#8250;</button>
            @else
                <span style="color:#444;padding:8px 14px;border:1px solid #333;border-radius:6px;">&#8250;</span>
            @endif
        </div>
    @endif
</div>
