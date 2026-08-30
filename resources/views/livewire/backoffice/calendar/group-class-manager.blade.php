{{-- Gestione corsi collettivi: CRUD, pannello iscritti e presenza --}}
<div>
    @if(session('success'))
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('success') }}
    </div>
    @endif
    <div class="row">
        {{-- Colonna lista occorrenze --}}
        <div class="{{ $showDetail ? 'col-md-7' : 'col-md-12' }}">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">Corsi collettivi</h3>
                    <div class="card-tools d-flex align-items-center gap-2">
                        {{-- Filtro status --}}
                        <select wire:model.live="filterStatus" class="form-control form-control-sm mr-2 filter-w-xs">
                            <option value="">Tutti</option>
                            <option value="planned">Programmati</option>
                            <option value="completed">Completati</option>
                            <option value="cancelled">Cancellati</option>
                        </select>
                        {{-- Ricerca --}}
                        <input type="text" wire:model.live.debounce.300ms="search"
                               placeholder="Cerca corso..."
                               class="form-control form-control-sm mr-2 filter-w-sm">
                        @role('gestore')
                        <button wire:click="generateOccurrences()"
                                wire:loading.attr="disabled"
                                class="btn btn-sm btn-outline-secondary mr-1"
                                title="Genera le occorrenze mancanti per i prossimi 28 giorni leggendo il palinsesto (ClassSchedule). Sicuro da eseguire più volte: non crea duplicati.">
                            <span wire:loading.remove wire:target="generateOccurrences">
                                <i class="fas fa-calendar-plus mr-1"></i> Genera occorrenze
                            </span>
                            <span wire:loading wire:target="generateOccurrences">
                                <i class="fas fa-spinner fa-spin mr-1"></i> Generazione...
                            </span>
                        </button>
                        @endrole
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
                                    <select wire:model="formTrainerId" class="form-control form-control-sm @error('formTrainerId') is-invalid @enderror">
                                        @foreach($trainers as $trainer)
                                            <option value="{{ $trainer->id }}">{{ $trainer->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('formTrainerId') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nome corso <span class="text-danger">*</span></label>
                                    <input type="text" wire:model="formName" class="form-control form-control-sm @error('formName') is-invalid @enderror"
                                           placeholder="Es. Spinning, CrossFit...">
                                    @error('formName') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Data e ora <span class="text-danger">*</span></label>
                                    <input type="datetime-local" wire:model="formScheduledAt" class="form-control form-control-sm @error('formScheduledAt') is-invalid @enderror">
                                    @error('formScheduledAt') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Durata (min)</label>
                                    <input type="number" wire:model="formDurationMinutes" min="15" max="480"
                                           class="form-control form-control-sm @error('formDurationMinutes') is-invalid @enderror">
                                    @error('formDurationMinutes') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Posti max</label>
                                    <input type="number" wire:model="formMaxParticipants" min="1" max="100"
                                           class="form-control form-control-sm @error('formMaxParticipants') is-invalid @enderror">
                                    @error('formMaxParticipants') <span class="invalid-feedback">{{ $message }}</span> @enderror
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
                            @php $occ = $class; @endphp
                            <tr>
                                <td>{{ $occ->groupClass->name }}</td>
                                <td>{{ $occ->date->format('d/m/Y') }} {{ substr($occ->start_time, 0, 5) }}</td>
                                <td>{{ $occ->trainer?->name }}</td>
                                <td>
                                    <span class="{{ $occ->is_full ? 'text-danger font-weight-bold' : '' }}">
                                        {{ $occ->confirmed_count }} / {{ $occ->capacity }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $cls = match($occ->status) {
                                            'planned'   => 'success',
                                            'completed' => 'info',
                                            'cancelled' => 'danger',
                                            default     => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge badge-{{ $cls }}">{{ $occ->status }}</span>
                                </td>
                                <td class="text-right table-actions">
                                    <button wire:click="openDetail({{ $occ->id }})"
                                            class="btn btn-sm btn-info mr-1" title="Dettaglio iscritti" aria-label="Iscritti {{ $occ->groupClass->name }}">
                                        <i class="fas fa-users" aria-hidden="true"></i>
                                    </button>
                                    @if($occ->status === 'planned')
                                    <button wire:click="completeOccurrence({{ $occ->id }})"
                                            wire:confirm="Marcare il corso come completato e registrare le presenze?"
                                            class="btn btn-sm btn-success mr-1" title="Completa corso" aria-label="Completa {{ $occ->groupClass->name }}">
                                        <i class="fas fa-check" aria-hidden="true"></i>
                                    </button>
                                    <button wire:click="openForm({{ $occ->id }})"
                                            class="btn btn-sm btn-primary mr-1" title="Modifica" aria-label="Modifica {{ $occ->groupClass->name }}">
                                        <i class="fas fa-edit" aria-hidden="true"></i>
                                    </button>
                                    @endif
                                    <button wire:click="deleteClass({{ $occ->id }})"
                                            wire:confirm="Eliminare/cancellare questo corso?"
                                            class="btn btn-sm btn-danger" title="Elimina" aria-label="Elimina {{ $occ->groupClass->name }}">
                                        <i class="fas fa-trash" aria-hidden="true"></i>
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
                    <h3 class="card-title">{{ $selectedClass->groupClass->name }}</h3>
                    <div class="card-tools">
                        <button wire:click="$set('showDetail', false)" class="btn btn-sm btn-secondary">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-2">
                    <p class="text-muted small mb-1">
                        <i class="fas fa-calendar mr-1"></i>
                        {{ $selectedClass->date->format('d/m/Y') }} {{ substr($selectedClass->start_time, 0, 5) }}
                        &mdash; {{ $selectedClass->groupClass->duration_minutes }} min
                    </p>
                    <p class="text-muted small mb-3">
                        <i class="fas fa-users mr-1"></i>
                        {{ $selectedClass->confirmed_count }} / {{ $selectedClass->capacity }} iscritti
                    </p>

                    @if($selectedClass->status === 'planned')
                    <button wire:click="completeOccurrence({{ $selectedClass->id }})"
                            wire:confirm="Marcare il corso come completato e registrare le presenze?"
                            class="btn btn-sm btn-success mb-3">
                        <i class="fas fa-check mr-1"></i> Completa corso
                    </button>
                    @endif

                    {{-- Iscritti confermati + presenza --}}
                    <h6 class="mb-2 font-weight-bold">Iscritti confermati</h6>
                    @if($selectedClass->confirmedBookings->isEmpty())
                        <p class="text-muted small">Nessun iscritto.</p>
                    @else
                        <ul class="list-group list-group-flush mb-3">
                            @foreach($selectedClass->confirmedBookings as $booking)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-2 py-1">
                                <span class="small">
                                    {{ $booking->member?->full_name }}
                                    @if($booking->attended_at)
                                        <span class="badge badge-success ml-1">Presente</span>
                                    @elseif($selectedClass->status === 'completed')
                                        <span class="badge badge-warning ml-1">Non registrato</span>
                                    @endif
                                </span>
                                <div class="d-flex gap-1">
                                    @if($selectedClass->status === 'completed' || $selectedClass->status === 'planned')
                                    <button wire:click="markNoShow({{ $booking->id }})"
                                            class="btn btn-sm btn-outline-warning mr-1"
                                            title="Assente/No-show"
                                            aria-label="Segna assente {{ $booking->member?->full_name }}">
                                        <i class="fas fa-user-times" aria-hidden="true"></i>
                                    </button>
                                    @endif
                                    @if($selectedClass->status === 'planned')
                                    <button wire:click="removeParticipant({{ $booking->id }})"
                                            wire:confirm="Rimuovere questo partecipante?"
                                            class="btn btn-sm btn-outline-danger"
                                            aria-label="Rimuovi {{ $booking->member?->full_name }}">
                                        <i class="fas fa-user-minus" aria-hidden="true"></i>
                                    </button>
                                    @endif
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    @endif

                    {{-- No-show --}}
                    @php $noShows = $selectedClass->bookings->where('status', 'no_show'); @endphp
                    @if($noShows->isNotEmpty())
                    <h6 class="mb-2 font-weight-bold">No-show</h6>
                    <ul class="list-group list-group-flush mb-3">
                        @foreach($noShows as $booking)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-2 py-1">
                            <span class="small text-muted">{{ $booking->member?->full_name }}</span>
                            <button wire:click="markAttended({{ $booking->id }})"
                                    class="btn btn-sm btn-outline-success"
                                    title="Segna presente"
                                    aria-label="Segna presente {{ $booking->member?->full_name }}">
                                <i class="fas fa-user-check" aria-hidden="true"></i>
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
                                        class="btn btn-sm btn-outline-danger"
                                        aria-label="Rimuovi {{ $waitlisted->member?->full_name }} dalla lista d'attesa">
                                    <i class="fas fa-times" aria-hidden="true"></i>
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
