<div>
    <x-bo.filters>
        <div class="row">
            <div class="col-md-4">
                <label class="small">Cerca</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cerca esercizio..." class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="small">Gruppo muscolare</label>
                <select wire:model.live="muscleGroup" class="form-control form-control-sm">
                    <option value="">Tutti</option>
                    <option value="chest">Petto</option>
                    <option value="back">Schiena</option>
                    <option value="shoulders">Spalle</option>
                    <option value="arms">Braccia</option>
                    <option value="legs">Gambe</option>
                    <option value="core">Core</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small">Meccanica</label>
                <select wire:model.live="mechanic" class="form-control form-control-sm">
                    <option value="">Tutti</option>
                    <option value="compound">Compound</option>
                    <option value="isolation">Isolamento</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small">Livello</label>
                <select wire:model.live="skillLevel" class="form-control form-control-sm">
                    <option value="">Tutti</option>
                    <option value="beginner">Principiante</option>
                    <option value="intermediate">Intermedio</option>
                    <option value="advanced">Avanzato</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small">Attrezzi</label>
                <select wire:model.live="equipmentFilter" multiple size="1" class="form-control form-control-sm" style="height: auto">
                    @foreach ($allEquipment as $eq)
                        <option value="{{ $eq['id'] }}">{{ $eq['name_it'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-bo.filters>

    <x-bo.card bodyClass="p-0">
        <x-slot name="actions">
            <a href="{{ route('backoffice.exercises.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Nuovo esercizio
            </a>
        </x-slot>

        <div class="table-responsive">
        <table class="table table-sm table-striped table-hover mb-0">
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
                        <td class="table-actions">
                            <a href="{{ route('backoffice.exercises.show', $exercise) }}" class="btn btn-sm btn-primary" aria-label="Dettaglio {{ $exercise->name_it }}" title="Dettaglio">
                                <i class="fas fa-eye"></i>
                            </a>
                            @role('gestore')
                                <a href="{{ route('backoffice.exercises.edit', $exercise) }}" class="btn btn-sm btn-primary" aria-label="Modifica {{ $exercise->name_it }}" title="Modifica">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <button
                                    type="button"
                                    wire:click="deleteExercise({{ $exercise->id }})"
                                    wire:confirm="Eliminare l'esercizio '{{ $exercise->name_it }}'?"
                                    class="btn btn-sm btn-primary"
                                    aria-label="Elimina {{ $exercise->name_it }}"
                                    title="Elimina"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            @endrole
                        </td>
                    </tr>
                @empty
                    <x-bo.empty :colspan="6">Nessun esercizio trovato.</x-bo.empty>
                @endforelse
            </tbody>
        </table>
        </div>

        <x-slot name="footer">
            <x-bo.pagination :paginator="$exercises" />
        </x-slot>
    </x-bo.card>
</div>
