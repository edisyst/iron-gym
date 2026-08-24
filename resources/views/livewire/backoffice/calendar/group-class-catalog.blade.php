<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Catalogo corsi collettivi</h4>
        @role('gestore')
        <button class="btn btn-primary btn-sm" wire:click="openForm()">
            <i class="fas fa-plus mr-1"></i> Nuovo corso
        </button>
        @endrole
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('error') }}
        </div>
    @endif

    {{-- Form creazione / modifica --}}
    @if ($showForm)
        <div class="card card-outline card-primary mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ $editingId ? 'Modifica corso' : 'Nuovo corso' }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nome <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('formName') is-invalid @enderror"
                                   wire:model="formName" placeholder="Es. Yoga, Spinning, CrossFit">
                            @error('formName')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Durata (min) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('formDurationMinutes') is-invalid @enderror"
                                   wire:model="formDurationMinutes" min="15" max="480">
                            @error('formDurationMinutes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Capienza predefinita <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('formDefaultCapacity') is-invalid @enderror"
                                   wire:model="formDefaultCapacity" min="1" max="200">
                            @error('formDefaultCapacity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Sala</label>
                            <input type="text" class="form-control @error('formRoom') is-invalid @enderror"
                                   wire:model="formRoom" placeholder="Es. Sala A">
                            @error('formRoom')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Colore</label>
                            <input type="color" class="form-control form-control-color"
                                   wire:model="formColor" style="height:38px;padding:2px 4px;">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Descrizione</label>
                            <textarea class="form-control @error('formDescription') is-invalid @enderror"
                                      wire:model="formDescription" rows="2"></textarea>
                            @error('formDescription')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="formIsActive"
                               wire:model="formIsActive">
                        <label class="custom-control-label" for="formIsActive">Corso attivo</label>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                <button class="btn btn-primary btn-sm" wire:click="save()" wire:loading.attr="disabled">
                    <span wire:loading wire:target="save" class="spinner-border spinner-border-sm mr-1"></span>
                    Salva
                </button>
                <button class="btn btn-secondary btn-sm" wire:click="cancelForm()">Annulla</button>
            </div>
        </div>
    @endif

    {{-- Tabella corsi --}}
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Durata</th>
                        <th>Capienza</th>
                        <th>Sala</th>
                        <th>Prossimi</th>
                        <th>Stato</th>
                        <th class="text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($classes as $gc)
                        <tr>
                            <td>
                                <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:{{ $gc->color ?? '#E85D04' }};margin-right:6px;vertical-align:middle;"></span>
                                <strong>{{ $gc->name }}</strong>
                                @if ($gc->description)
                                    <small class="text-muted d-block">{{ Str::limit($gc->description, 60) }}</small>
                                @endif
                            </td>
                            <td>{{ $gc->duration_minutes }} min</td>
                            <td>{{ $gc->default_capacity }}</td>
                            <td>{{ $gc->room ?? '—' }}</td>
                            <td>
                                <span class="badge badge-{{ $gc->future_count > 0 ? 'info' : 'secondary' }}">
                                    {{ $gc->future_count }}
                                </span>
                            </td>
                            <td>
                                @if ($gc->is_active)
                                    <span class="badge badge-success">Attivo</span>
                                @else
                                    <span class="badge badge-secondary">Inattivo</span>
                                @endif
                            </td>
                            <td class="text-right table-actions">
                                @role('gestore')
                                <button class="btn btn-sm btn-outline-primary"
                                        wire:click="openForm({{ $gc->id }})"
                                        aria-label="Modifica {{ $gc->name }}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-{{ $gc->is_active ? 'warning' : 'success' }}"
                                        wire:click="toggleActive({{ $gc->id }})"
                                        aria-label="{{ $gc->is_active ? 'Disattiva' : 'Attiva' }} {{ $gc->name }}">
                                    <i class="fas fa-{{ $gc->is_active ? 'pause' : 'play' }}"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger"
                                        wire:click="deleteClass({{ $gc->id }})"
                                        wire:confirm="Eliminare '{{ $gc->name }}'? L'operazione è irreversibile."
                                        aria-label="Elimina {{ $gc->name }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endrole
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Nessun corso nel catalogo. Clicca "Nuovo corso" per iniziare.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
