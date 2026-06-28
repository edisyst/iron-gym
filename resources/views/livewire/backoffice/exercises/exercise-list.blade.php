<div>
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex flex-wrap gap-2">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cerca esercizio..."
                        class="form-control form-control-sm"
                        style="width: 220px"
                    >
                    <select wire:model.live="muscleGroup" class="form-control form-control-sm" style="width: 160px">
                        <option value="">Tutti i gruppi</option>
                        <option value="chest">Petto</option>
                        <option value="back">Schiena</option>
                        <option value="shoulders">Spalle</option>
                        <option value="arms">Braccia</option>
                        <option value="legs">Gambe</option>
                        <option value="core">Core</option>
                    </select>
                    <select wire:model.live="mechanic" class="form-control form-control-sm" style="width: 140px">
                        <option value="">Meccanica</option>
                        <option value="compound">Compound</option>
                        <option value="isolation">Isolamento</option>
                    </select>
                    <select wire:model.live="skillLevel" class="form-control form-control-sm" style="width: 140px">
                        <option value="">Livello</option>
                        <option value="beginner">Principiante</option>
                        <option value="intermediate">Intermedio</option>
                        <option value="advanced">Avanzato</option>
                    </select>
                    <select wire:model.live="equipmentFilter" multiple size="1" class="form-control form-control-sm" style="width: 160px; height: auto">
                        @foreach ($allEquipment as $eq)
                            <option value="{{ $eq['id'] }}">{{ $eq['name_it'] }}</option>
                        @endforeach
                    </select>
                </div>
                <a href="{{ route('backoffice.exercises.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Nuovo esercizio
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Muscolo primario</th>
                        <th>Pattern</th>
                        <th>Meccanica</th>
                        <th>Livello</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($exercises as $exercise)
                        @php
                            // Muscolo primary con contribution_pct più alto
                            $primaryMuscle = $exercise->muscles
                                ->filter(fn ($m) => $m->pivot->role === 'primary')
                                ->sortByDesc(fn ($m) => $m->pivot->contribution_pct)
                                ->first();

                            // Pattern attivo: compound o joint_action
                            $pattern  = $exercise->compoundPattern ?? $exercise->jointAction;
                            $isCompound = $exercise->compoundPattern !== null;
                        @endphp
                        @php
                            $thumbUrl = null;
                            foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
                                if (file_exists(public_path("images/exercises/{$exercise->slug}.{$ext}"))) {
                                    $thumbUrl = asset("images/exercises/{$exercise->slug}.{$ext}");
                                    break;
                                }
                            }
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:48px; height:48px; flex-shrink:0; background:#f4f6f9; border-radius:4px; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                                        @if ($thumbUrl)
                                            <img src="{{ $thumbUrl }}" alt="{{ $exercise->name_it }}"
                                                 style="width:48px; height:48px; object-fit:cover;">
                                        @else
                                            <img src="{{ asset('images/exercises/no-image.svg') }}"
                                                 alt="No image"
                                                 style="width:48px; height:48px; object-fit:cover; opacity:.6;">
                                        @endif
                                    </div>
                                    <div>
                                        <a href="{{ route('backoffice.exercises.show', $exercise) }}" class="text-dark font-weight-bold">{{ $exercise->name_it }}</a>
                                        <br><small class="text-muted">{{ $exercise->slug }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $primaryMuscle?->name_it ?? '—' }}</td>
                            <td>
                                @if ($pattern)
                                    <span class="text-sm">{{ $pattern->slug }}</span><br>
                                    @if ($isCompound)
                                        <span class="badge badge-info badge-sm">Compound</span>
                                    @else
                                        <span class="badge badge-secondary badge-sm">Joint action</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($exercise->mechanic === 'compound')
                                    <span class="badge badge-primary">Compound</span>
                                @else
                                    <span class="badge badge-warning">Isolamento</span>
                                @endif
                            </td>
                            <td>
                                @if ($exercise->skill_level === 'beginner')
                                    <span class="badge badge-success">Principiante</span>
                                @elseif ($exercise->skill_level === 'intermediate')
                                    <span class="badge badge-warning">Intermedio</span>
                                @else
                                    <span class="badge badge-danger">Avanzato</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('backoffice.exercises.show', $exercise) }}" class="btn btn-xs btn-default" aria-label="Dettaglio {{ $exercise->name_it }}">
                                    Dettaglio
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nessun esercizio trovato.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $exercises->links() }}
        </div>
    </div>
</div>
