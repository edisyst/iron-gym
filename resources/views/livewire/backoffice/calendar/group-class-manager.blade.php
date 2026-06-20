{{-- Gestione corsi collettivi: CRUD e pannello iscritti --}}
<div>
    <div class="row">
        {{-- Colonna lista corsi --}}
        <div class="{{ $showDetail ? 'col-md-7' : 'col-md-12' }}">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">Corsi collettivi</h3>
                    <div class="card-tools d-flex align-items-center gap-2">
                        {{-- Filtro status --}}
                        <select wire:model.live="filterStatus" class="form-control form-control-sm mr-2" style="width:140px">
                            <option value="">Tutti</option>
                            <option value="scheduled">Programmati</option>
                            <option value="completed">Completati</option>
                            <option value="cancelled">Cancellati</option>
                        </select>
                        {{-- Ricerca --}}
                        <input type="text" wire:model.live.debounce.300ms="search"
                               placeholder="Cerca corso..."
                               class="form-control form-control-sm mr-2" style="width:180px">
                        <button wire:click="openForm()" class="btn btn-sm btn-warning">
                            <i class="fas fa-plus mr-1"></i> Nuovo corso
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    {{-- Form inline creazione/modifica --}}
                    @if($showForm)
                    <div class="p-3 border-bottom bg-light">
                        <h5 class="mb-3">{{ $editingClassId ? 'Modifica corso' : 'Nuovo corso' }}</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Trainer <span class="text-danger">*</span></label>
                                    <select wire:model="formTrainerId" class="form-control form-control-sm">
                                        @foreach($trainers as $trainer)
                                            <option value="{{ $trainer->id }}">{{ $trainer->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('formTrainerId') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nome corso <span class="text-danger">*</span></label>
                                    <input type="text" wire:model="formName" class="form-control form-control-sm"
                                           placeholder="Es. Spinning, CrossFit...">
                                    @error('formName') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Data e ora <span class="text-danger">*</span></label>
                                    <input type="datetime-local" wire:model="formScheduledAt" class="form-control form-control-sm">
                                    @error('formScheduledAt') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Durata (min)</label>
                                    <input type="number" wire:model="formDurationMinutes" min="15" max="480"
                                           class="form-control form-control-sm">
                                    @error('formDurationMinutes') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Posti max</label>
                                    <input type="number" wire:model="formMaxParticipants" min="1" max="100"
                                           class="form-control form-control-sm">
                                    @error('formMaxParticipants') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Descrizione</label>
                                    <textarea wire:model="formDescription" class="form-control form-control-sm" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button wire:click="save" class="btn btn-sm btn-warning mr-1">
                                <span wire:loading wire:target="save" class="spinner-border spinner-border-sm"></span>
                                Salva
                            </button>
                            <button wire:click="$set('showForm', false)" class="btn btn-sm btn-secondary">Annulla</button>
                        </div>
                    </div>
                    @endif

                    <table class="table table-hover table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Nome</th>
                                <th>Data/Ora</th>
                                <th>Trainer</th>
                                <th>Iscritti</th>
                                <th>Status</th>
                                <th class="text-right">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($classes as $class)
                            <tr>
                                <td>{{ $class->name }}</td>
                                <td>{{ $class->scheduled_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $class->trainer?->name }}</td>
                                <td>
                                    <span class="{{ $class->is_full ? 'text-danger font-weight-bold' : '' }}">
                                        {{ $class->confirmed_count }} / {{ $class->max_participants }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $cls = match($class->status) {
                                            'scheduled' => 'success',
                                            'completed' => 'info',
                                            'cancelled' => 'danger',
                                            default     => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge badge-{{ $cls }}">{{ $class->status }}</span>
                                </td>
                                <td class="text-right">
                                    <button wire:click="openDetail({{ $class->id }})"
                                            class="btn btn-xs btn-info mr-1" title="Dettaglio iscritti">
                                        <i class="fas fa-users"></i>
                                    </button>
                                    <button wire:click="openForm({{ $class->id }})"
                                            class="btn btn-xs btn-primary mr-1" title="Modifica">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button wire:click="deleteClass({{ $class->id }})"
                                            wire:confirm="Eliminare/cancellare questo corso?"
                                            class="btn btn-xs btn-danger" title="Elimina">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Nessun corso trovato.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($classes->hasPages())
                <div class="card-footer">
                    {{ $classes->links() }}
                </div>
                @endif
            </div>
        </div>

        {{-- Pannello dettaglio iscritti --}}
        @if($showDetail && $selectedClass)
        <div class="col-md-5">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">{{ $selectedClass->name }}</h3>
                    <div class="card-tools">
                        <button wire:click="$set('showDetail', false)" class="btn btn-sm btn-secondary">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-2">
                    <p class="text-muted small mb-1">
                        <i class="fas fa-calendar mr-1"></i>
                        {{ $selectedClass->scheduled_at->format('d/m/Y H:i') }}
                        &mdash; {{ $selectedClass->duration_minutes }} min
                    </p>
                    <p class="text-muted small mb-3">
                        <i class="fas fa-users mr-1"></i>
                        {{ $selectedClass->confirmed_count }} / {{ $selectedClass->max_participants }} iscritti
                    </p>

                    {{-- Iscritti confermati --}}
                    <h6 class="mb-2 font-weight-bold">Iscritti confermati</h6>
                    @if($selectedClass->confirmedBookings->isEmpty())
                        <p class="text-muted small">Nessun iscritto.</p>
                    @else
                        <ul class="list-group list-group-flush mb-3">
                            @foreach($selectedClass->confirmedBookings as $booking)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-2 py-1">
                                <span class="small">{{ $booking->member?->full_name }}</span>
                                <button wire:click="removeParticipant({{ $booking->id }})"
                                        wire:confirm="Rimuovere questo partecipante?"
                                        class="btn btn-xs btn-outline-danger">
                                    <i class="fas fa-user-minus"></i>
                                </button>
                            </li>
                            @endforeach
                        </ul>
                    @endif

                    {{-- Lista d'attesa --}}
                    <h6 class="mb-2 font-weight-bold">Lista d'attesa</h6>
                    @if($selectedClass->waitlist->isEmpty())
                        <p class="text-muted small">Nessuno in lista d'attesa.</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($selectedClass->waitlist as $waitlisted)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-2 py-1">
                                <span class="small">
                                    <span class="badge badge-secondary mr-1">#{{ $waitlisted->position }}</span>
                                    {{ $waitlisted->member?->full_name }}
                                </span>
                                <button wire:click="removeParticipant({{ $waitlisted->id }})"
                                        wire:confirm="Rimuovere dalla lista d'attesa?"
                                        class="btn btn-xs btn-outline-danger">
                                    <i class="fas fa-times"></i>
                                </button>
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
