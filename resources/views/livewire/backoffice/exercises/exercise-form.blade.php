<div>
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form wire:submit="save">

        {{-- Sezione: informazioni base --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Informazioni base</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label>Nome <span class="text-danger">*</span></label>
                    <input type="text" wire:model.live="nameIt" class="form-control @error('nameIt') is-invalid @enderror" placeholder="Es. Panca piana con bilanciere">
                    @error('nameIt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Slug con sblocco via Alpine --}}
                <div class="form-group" x-data="{ locked: {{ $exerciseId ? 'false' : 'true' }} }">
                    <label>Slug <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" wire:model="slug" class="form-control @error('slug') is-invalid @enderror"
                               :readonly="locked" placeholder="generato automaticamente">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary btn-sm" @click="locked = !locked"
                                    x-text="locked ? 'Modifica' : 'Blocca'"></button>
                        </div>
                        @error('slug') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label>Descrizione</label>
                    <textarea wire:model="description" class="form-control" rows="3" placeholder="Descrizione tecnica dell'esercizio..."></textarea>
                </div>

                <div class="form-group">
                    <label>Descrizione esecuzione</label>
                    <textarea wire:model="executionDescription" class="form-control" rows="5" placeholder="Istruzioni passo-passo sull'esecuzione corretta dell'esercizio..."></textarea>
                </div>
            </div>
        </div>

        {{-- Sezione: media --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Immagine e Video</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Immagine esercizio</label>
                            @php
                                $imgExts = ['png', 'jpg', 'jpeg', 'webp'];
                                $existingImg = null;
                                if ($exercise) {
                                    foreach ($imgExts as $ext) {
                                        if (file_exists(public_path("images/exercises/{$exercise->slug}.{$ext}"))) {
                                            $existingImg = asset("images/exercises/{$exercise->slug}.{$ext}");
                                            break;
                                        }
                                    }
                                }
                            @endphp
                            @if ($existingImg)
                                <div class="mb-2">
                                    <img src="{{ $existingImg }}" alt="{{ $nameIt }}"
                                         class="img-thumbnail" style="max-height:120px; object-fit:contain;">
                                    <small class="d-block text-muted mt-1">Immagine attuale</small>
                                </div>
                            @endif
                            @if ($imageFile)
                                <div class="mb-2">
                                    <img src="{{ $imageFile->temporaryUrl() }}" alt="Anteprima"
                                         class="img-thumbnail" style="max-height:120px; object-fit:contain;">
                                    <small class="d-block text-muted mt-1">Anteprima caricamento</small>
                                </div>
                            @endif
                            <input type="file" wire:model="imageFile" class="form-control-file @error('imageFile') is-invalid @enderror"
                                   accept="image/png,image/jpeg,image/webp">
                            <small class="form-text text-muted">PNG, JPG o WebP, max 4 MB. Il file verrà salvato come <code>{{ $slug ?: 'slug' }}.{ext}</code>.</small>
                            @error('imageFile') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>URL video (opzionale)</label>
                            <input type="url" wire:model="videoUrl" class="form-control @error('videoUrl') is-invalid @enderror" placeholder="https://...">
                            @error('videoUrl') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group">
                            <label>URL thumbnail (opzionale)</label>
                            <input type="url" wire:model="thumbnailUrl" class="form-control @error('thumbnailUrl') is-invalid @enderror" placeholder="https://...">
                            @error('thumbnailUrl') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sezione: pattern motorio --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Pattern motorio</h3></div>
            <div class="card-body" x-data>
                <div class="d-flex gap-3 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" wire:model.live="patternType"
                               value="compound_pattern" id="pt_compound">
                        <label class="form-check-label" for="pt_compound">Compound pattern</label>
                    </div>
                    <div class="form-check ml-4">
                        <input class="form-check-input" type="radio" wire:model.live="patternType"
                               value="joint_action" id="pt_joint">
                        <label class="form-check-label" for="pt_joint">Azione articolare (Joint action)</label>
                    </div>
                </div>

                @if ($patternType === 'compound_pattern')
                    <div class="form-group">
                        <label>Compound pattern <span class="text-danger">*</span></label>
                        <select wire:model="compoundPatternId" class="form-control @error('compoundPatternId') is-invalid @enderror">
                            <option value="">— Seleziona —</option>
                            @foreach ($compoundPatterns as $cp)
                                <option value="{{ $cp->id }}">{{ $cp->slug }} — {{ $cp->name_it }}</option>
                            @endforeach
                        </select>
                        @error('compoundPatternId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                @else
                    <div class="form-group">
                        <label>Joint action <span class="text-danger">*</span></label>
                        <select wire:model="jointActionId" class="form-control @error('jointActionId') is-invalid @enderror">
                            <option value="">— Seleziona —</option>
                            @foreach ($jointActions as $ja)
                                <option value="{{ $ja->id }}">{{ $ja->slug }} — {{ $ja->name_it }}</option>
                            @endforeach
                        </select>
                        @error('jointActionId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                @endif
            </div>
        </div>

        {{-- Sezione: classificazione --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Classificazione</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Meccanica</label>
                            <select wire:model="mechanic" class="form-control">
                                <option value="compound">Compound</option>
                                <option value="isolation">Isolation</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Piano di movimento</label>
                            <select wire:model="plane" class="form-control">
                                <option value="sagittal">Sagittale</option>
                                <option value="frontal">Frontale</option>
                                <option value="transverse">Trasversale</option>
                                <option value="multiplanar">Multiplanare</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Lateralità</label>
                            <select wire:model="laterality" class="form-control">
                                <option value="bilateral">Bilaterale</option>
                                <option value="unilateral_alternating">Unilaterale alternato</option>
                                <option value="unilateral_isolated">Unilaterale isolato</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Livello</label>
                            <select wire:model="skillLevel" class="form-control">
                                <option value="beginner">Principiante</option>
                                <option value="intermediate">Intermedio</option>
                                <option value="advanced">Avanzato</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Tipo misurazione</label>
                            <select wire:model="measurementType" class="form-control">
                                <option value="reps_weight">Reps + peso</option>
                                <option value="reps_only">Solo reps</option>
                                <option value="time">Tempo</option>
                                <option value="time_weight">Tempo + peso</option>
                                <option value="distance">Distanza</option>
                                <option value="isometric_hold">Isometrica</option>
                            </select>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Sezione: attrezzatura --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Attrezzatura</h3></div>
            <div class="card-body">
                <div class="row">
                    @foreach ($allEquipment as $eq)
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                       wire:model="selectedEquipment"
                                       value="{{ $eq->id }}"
                                       id="eq_{{ $eq->id }}">
                                <label class="form-check-label" for="eq_{{ $eq->id }}">{{ $eq->name_it }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Sezione: muscoli coinvolti --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Muscoli coinvolti</h3></div>
            <div class="card-body">
                @error('muscleData') <div class="alert alert-danger py-2">{{ $message }}</div> @enderror

                @php
                    $musclesByGroup = $allMuscles->groupBy('muscle_group');
                    $groupLabels = [
                        'chest'     => 'Petto',
                        'back'      => 'Schiena',
                        'shoulders' => 'Spalle',
                        'arms'      => 'Braccia',
                        'legs'      => 'Gambe',
                        'core'      => 'Core',
                    ];
                @endphp

                @foreach ($musclesByGroup as $group => $muscles)
                    <h6 class="text-uppercase text-muted mb-2 mt-3">{{ $groupLabels[$group] ?? $group }}</h6>
                    <div class="row">
                        @foreach ($muscles as $muscle)
                            @php $mId = (string) $muscle->id; @endphp
                            <div class="col-md-4 mb-2" x-data="{ checked: @entangle('muscleData.' . $mId . '.selected') }">
                                <div class="border rounded p-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               wire:model.live="muscleData.{{ $mId }}.selected"
                                               id="muscle_{{ $mId }}"
                                               x-model="checked">
                                        <label class="form-check-label font-weight-bold" for="muscle_{{ $mId }}">
                                            {{ $muscle->name_it }}
                                        </label>
                                    </div>
                                    <div x-show="checked" x-cloak class="mt-2">
                                        <div class="row">
                                            <div class="col-7">
                                                <select wire:model="muscleData.{{ $mId }}.role" class="form-control form-control-sm">
                                                    <option value="primary">Primary</option>
                                                    <option value="secondary">Secondary</option>
                                                    <option value="stabilizer">Stabilizer</option>
                                                </select>
                                            </div>
                                            <div class="col-5">
                                                <div class="input-group input-group-sm">
                                                    <input type="number" wire:model="muscleData.{{ $mId }}.pct"
                                                           class="form-control form-control-sm @error('muscleData.'.$mId.'.pct') is-invalid @enderror"
                                                           min="0" max="100" placeholder="%">
                                                    <div class="input-group-append"><span class="input-group-text">%</span></div>
                                                </div>
                                                @error('muscleData.'.$mId.'.pct')
                                                    <div class="text-danger" style="font-size: 0.75em">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Bottoni azione --}}
        <div class="d-flex align-items-center gap-2 mb-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Salva
            </button>
            <a href="{{ route('backoffice.exercises.index') }}" class="btn btn-default ml-2">
                Annulla
            </a>

            @if ($exerciseId)
                {{-- Modale archivio con Alpine --}}
                <div x-data="{ open: false }" class="ml-auto">
                    <button type="button" class="btn btn-danger btn-sm" @click="open = true">
                        <i class="fas fa-archive"></i> Archivia
                    </button>
                    <div x-show="open" x-cloak style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">
                        <div class="modal-dialog mb-0" style="max-width: 400px;">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Conferma archiviazione</h5>
                                </div>
                                <div class="modal-body">
                                    <p>Vuoi archiviare l'esercizio <strong>{{ $nameIt }}</strong>? Sarà nascosto dal catalogo ma i dati storici saranno preservati.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" @click="open = false">Annulla</button>
                                    <button type="button" class="btn btn-danger" wire:click="archive">Conferma archiviazione</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

    </form>
</div>
