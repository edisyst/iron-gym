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
