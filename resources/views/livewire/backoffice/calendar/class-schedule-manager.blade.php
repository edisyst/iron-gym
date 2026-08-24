{{-- Gestione palinsesto corsi collettivi (ClassSchedule) --}}
<div>
    @if(session('success'))
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('error') }}
    </div>
    @endif

    <div class="card card-outline card-warning">
        <div class="card-header">
            <h3 class="card-title">Palinsesto corsi</h3>
            <div class="card-tools">
                <button wire:click="openForm()" class="btn btn-sm btn-warning">
                    <i class="fas fa-plus mr-1"></i> Nuovo palinsesto
                </button>
            </div>
        </div>
        <div class="card-body p-0">

            {{-- Form creazione/modifica --}}
            @if($showForm)
            <div class="p-3 border-bottom bg-light">
                <h5 class="mb-3">{{ $editingId ? 'Modifica palinsesto' : 'Nuovo palinsesto' }}</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Corso <span class="text-danger">*</span></label>
                            <select wire:model="formGroupClassId"
                                    class="form-control form-control-sm @error('formGroupClassId') is-invalid @enderror">
                                <option value="0">Seleziona corso...</option>
                                @foreach($groupClasses as $gc)
                                    <option value="{{ $gc->id }}">{{ $gc->name }}</option>
                                @endforeach
                            </select>
                            @error('formGroupClassId') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Giorno <span class="text-danger">*</span></label>
                            <select wire:model="formWeekday"
                                    class="form-control form-control-sm @error('formWeekday') is-invalid @enderror">
                                @foreach($weekdayLabels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('formWeekday') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Orario <span class="text-danger">*</span></label>
                            <input type="time" wire:model="formStartTime"
                                   class="form-control form-control-sm @error('formStartTime') is-invalid @enderror">
                            @error('formStartTime') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Trainer <span class="text-danger">*</span></label>
                            <select wire:model="formTrainerId"
                                    class="form-control form-control-sm @error('formTrainerId') is-invalid @enderror">
                                @foreach($trainers as $trainer)
                                    <option value="{{ $trainer->id }}">{{ $trainer->name }}</option>
                                @endforeach
                            </select>
                            @error('formTrainerId') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Valido dal <span class="text-danger">*</span></label>
                            <input type="date" wire:model="formValidFrom"
                                   class="form-control form-control-sm @error('formValidFrom') is-invalid @enderror">
                            @error('formValidFrom') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Valido fino al</label>
                            <input type="date" wire:model="formValidUntil"
                                   class="form-control form-control-sm @error('formValidUntil') is-invalid @enderror">
                            @error('formValidUntil') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-center mt-2">
                        <div class="form-check">
                            <input type="checkbox" wire:model="formIsActive"
                                   id="formIsActive" class="form-check-input">
                            <label class="form-check-label" for="formIsActive">Attivo</label>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-2">
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
                        <th>Corso</th>
                        <th>Giorno</th>
                        <th>Orario</th>
                        <th>Trainer</th>
                        <th>Validità</th>
                        <th>Stato</th>
                        <th class="text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $schedule)
                    <tr class="{{ $schedule->is_active ? '' : 'text-muted' }}">
                        <td>{{ $schedule->groupClass->name }}</td>
                        <td>{{ $weekdayLabels[$schedule->weekday] }}</td>
                        <td>{{ substr($schedule->start_time, 0, 5) }}</td>
                        <td>{{ $schedule->trainer?->name ?? '—' }}</td>
                        <td class="small">
                            {{ $schedule->valid_from?->format('d/m/Y') ?? '—' }}
                            @if($schedule->valid_until)
                                &rarr; {{ $schedule->valid_until->format('d/m/Y') }}
                            @else
                                &rarr; ∞
                            @endif
                        </td>
                        <td>
                            @if($schedule->is_active)
                                <span class="badge badge-success">Attivo</span>
                            @else
                                <span class="badge badge-secondary">Inattivo</span>
                            @endif
                        </td>
                        <td class="text-right table-actions">
                            <button wire:click="toggleActive({{ $schedule->id }})"
                                    class="btn btn-sm {{ $schedule->is_active ? 'btn-outline-secondary' : 'btn-outline-success' }} mr-1"
                                    title="{{ $schedule->is_active ? 'Disattiva' : 'Attiva' }}"
                                    aria-label="{{ $schedule->is_active ? 'Disattiva' : 'Attiva' }} palinsesto {{ $schedule->groupClass->name }}">
                                <i class="fas {{ $schedule->is_active ? 'fa-pause' : 'fa-play' }}" aria-hidden="true"></i>
                            </button>
                            <button wire:click="openForm({{ $schedule->id }})"
                                    class="btn btn-sm btn-primary mr-1"
                                    title="Modifica"
                                    aria-label="Modifica palinsesto {{ $schedule->groupClass->name }}">
                                <i class="fas fa-edit" aria-hidden="true"></i>
                            </button>
                            <button wire:click="deleteSchedule({{ $schedule->id }})"
                                    wire:confirm="Eliminare questo palinsesto? Le occorrenze già create non vengono rimosse."
                                    class="btn btn-sm btn-danger"
                                    title="Elimina"
                                    aria-label="Elimina palinsesto {{ $schedule->groupClass->name }}">
                                <i class="fas fa-trash" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            Nessun palinsesto. Crea il primo con il pulsante in alto a destra.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($schedules->hasPages())
        <div class="card-footer">
            {{ $schedules->links() }}
        </div>
        @endif
    </div>
</div>
