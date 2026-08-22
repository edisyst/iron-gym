{{-- Orari di apertura palestra: slot settimanali e eccezioni/festività --}}
<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- Slot settimanali ricorrenti --}}
    {{-- ============================================================ --}}
    <div class="card card-outline card-success">
        <div class="card-header">
            <h3 class="card-title">Orari settimanali</h3>
            @if($canEdit)
            <div class="card-tools">
                <button wire:click="$toggle('showAddSlot')" class="btn btn-sm btn-success">
                    <i class="fas fa-plus mr-1"></i> Aggiungi
                </button>
            </div>
            @endif
        </div>
        <div class="card-body p-0">
            {{-- Form aggiunta --}}
            @if($showAddSlot && $canEdit)
            <div class="p-3 border-bottom bg-light">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="small">Giorno</label>
                        <select wire:model="newDayOfWeek" class="form-control form-control-sm @error('newDayOfWeek') is-invalid @enderror">
                            @foreach($daysOfWeek as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('newDayOfWeek') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="small">Dalle</label>
                        <input type="time" wire:model="newStartTime" class="form-control form-control-sm @error('newStartTime') is-invalid @enderror">
                        @error('newStartTime') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="small">Alle</label>
                        <input type="time" wire:model="newEndTime" class="form-control form-control-sm @error('newEndTime') is-invalid @enderror">
                        @error('newEndTime') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <button wire:click="addSlot" class="btn btn-sm btn-success mr-1">
                            <span wire:loading wire:target="addSlot" class="spinner-border spinner-border-sm"></span>
                            Salva
                        </button>
                        <button wire:click="$set('showAddSlot', false)" class="btn btn-sm btn-secondary">Annulla</button>
                    </div>
                </div>
            </div>
            @endif

            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>Giorno</th>
                        <th>Dalle</th>
                        <th>Alle</th>
                        @if($canEdit)<th class="text-right table-actions">Azioni</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($slots as $slot)
                        @if($editingSlotId === $slot->id)
                        {{-- Riga di modifica inline --}}
                        <tr class="table-warning">
                            <td>
                                <select wire:model="editSlotDay" class="form-control form-control-sm @error('editSlotDay') is-invalid @enderror">
                                    @foreach($daysOfWeek as $val => $label)
                                        <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('editSlotDay') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </td>
                            <td>
                                <input type="time" wire:model="editSlotStart" class="form-control form-control-sm @error('editSlotStart') is-invalid @enderror">
                                @error('editSlotStart') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </td>
                            <td>
                                <input type="time" wire:model="editSlotEnd" class="form-control form-control-sm @error('editSlotEnd') is-invalid @enderror">
                                @error('editSlotEnd') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </td>
                            <td class="text-right">
                                <button wire:click="saveSlot" class="btn btn-sm btn-success mr-1" aria-label="Salva">
                                    <span wire:loading wire:target="saveSlot" class="spinner-border spinner-border-sm"></span>
                                    <i wire:loading.remove wire:target="saveSlot" class="fas fa-check"></i>
                                </button>
                                <button wire:click="cancelEditSlot" class="btn btn-sm btn-secondary" aria-label="Annulla">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                        @else
                        {{-- Riga normale --}}
                        <tr>
                            <td>{{ $daysOfWeek[$slot->day_of_week] ?? $slot->day_of_week }}</td>
                            <td>{{ substr($slot->start_time, 0, 5) }}</td>
                            <td>{{ substr($slot->end_time, 0, 5) }}</td>
                            @if($canEdit)
                            <td class="text-right">
                                <button wire:click="startEditSlot({{ $slot->id }})" class="btn btn-sm btn-primary mr-1" aria-label="Modifica slot">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button wire:click="deleteSlot({{ $slot->id }})"
                                        wire:confirm="Eliminare questo slot?"
                                        class="btn btn-sm btn-danger"
                                        aria-label="Elimina slot">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                            @endif
                        </tr>
                        @endif
                    @empty
                    <tr>
                        <td colspan="{{ $canEdit ? 4 : 3 }}" class="text-center text-muted py-3">
                            Nessun orario settimanale configurato.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- Eccezioni / festività --}}
    {{-- ============================================================ --}}
    <div class="card card-outline card-warning">
        <div class="card-header">
            <h3 class="card-title">Eccezioni e festività</h3>
            @if($canEdit)
            <div class="card-tools">
                <button wire:click="$toggle('showAddOverride')" class="btn btn-sm btn-warning">
                    <i class="fas fa-plus mr-1"></i> Aggiungi
                </button>
            </div>
            @endif
        </div>
        <div class="card-body p-0">
            {{-- Form aggiunta --}}
            @if($showAddOverride && $canEdit)
            <div class="p-3 border-bottom bg-light">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="small">Data</label>
                        <input type="date" wire:model="newDate" class="form-control form-control-sm @error('newDate') is-invalid @enderror">
                        @error('newDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="small">Tipo</label>
                        <select wire:model="newIsOpen" class="form-control form-control-sm">
                            <option value="1">Orario ridotto</option>
                            <option value="0">Chiusura totale</option>
                        </select>
                    </div>
                    <div class="col-md-2" x-data x-show="$wire.newIsOpen">
                        <label class="small">Dalle</label>
                        <input type="time" wire:model="newOverrideStart" class="form-control form-control-sm @error('newOverrideStart') is-invalid @enderror">
                        @error('newOverrideStart') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2" x-data x-show="$wire.newIsOpen">
                        <label class="small">Alle</label>
                        <input type="time" wire:model="newOverrideEnd" class="form-control form-control-sm @error('newOverrideEnd') is-invalid @enderror">
                        @error('newOverrideEnd') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="small">Note</label>
                        <input type="text" wire:model="newNotes" class="form-control form-control-sm" placeholder="Es. Natale">
                    </div>
                    <div class="col-md-2">
                        <div class="form-check mb-1">
                            <input type="checkbox" wire:model="newIsAnnual" id="newIsAnnual" class="form-check-input">
                            <label for="newIsAnnual" class="form-check-label small">Si ripete ogni anno</label>
                        </div>
                        <button wire:click="addOverride" class="btn btn-sm btn-warning mr-1">
                            <span wire:loading wire:target="addOverride" class="spinner-border spinner-border-sm"></span>
                            Salva
                        </button>
                        <button wire:click="$set('showAddOverride', false)" class="btn btn-sm btn-secondary">Annulla</button>
                    </div>
                </div>
            </div>
            @endif

            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Dalle</th>
                        <th>Alle</th>
                        <th>Note</th>
                        @if($canEdit)<th class="text-right table-actions">Azioni</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($overrides as $override)
                        @if($editingOverrideId === $override->id)
                        {{-- Riga di modifica inline --}}
                        <tr class="table-warning">
                            <td>
                                <input type="date" wire:model="editOverrideDate" class="form-control form-control-sm @error('editOverrideDate') is-invalid @enderror">
                                @error('editOverrideDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-check mt-1">
                                    <input type="checkbox" wire:model="editOverrideIsAnnual" id="editIsAnnual_{{ $override->id }}" class="form-check-input">
                                    <label for="editIsAnnual_{{ $override->id }}" class="form-check-label small">Ogni anno</label>
                                </div>
                            </td>
                            <td>
                                <select wire:model="editOverrideIsOpen" class="form-control form-control-sm">
                                    <option value="1">Orario ridotto</option>
                                    <option value="0">Chiusura totale</option>
                                </select>
                            </td>
                            <td x-data x-show="$wire.editOverrideIsOpen">
                                <input type="time" wire:model="editOverrideStart" class="form-control form-control-sm @error('editOverrideStart') is-invalid @enderror">
                                @error('editOverrideStart') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </td>
                            <td x-data x-show="$wire.editOverrideIsOpen">
                                <input type="time" wire:model="editOverrideEnd" class="form-control form-control-sm @error('editOverrideEnd') is-invalid @enderror">
                                @error('editOverrideEnd') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </td>
                            <td>
                                <input type="text" wire:model="editOverrideNotes" class="form-control form-control-sm" placeholder="Note">
                            </td>
                            <td class="text-right">
                                <button wire:click="saveOverride" class="btn btn-sm btn-success mr-1" aria-label="Salva">
                                    <span wire:loading wire:target="saveOverride" class="spinner-border spinner-border-sm"></span>
                                    <i wire:loading.remove wire:target="saveOverride" class="fas fa-check"></i>
                                </button>
                                <button wire:click="cancelEditOverride" class="btn btn-sm btn-secondary" aria-label="Annulla">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                        @else
                        {{-- Riga normale --}}
                        <tr>
                            <td>
                                @if($override->is_annual)
                                    {{ $override->specific_date->format('d/m') }}
                                    <span class="badge badge-secondary ml-1">ogni anno</span>
                                @else
                                    {{ $override->specific_date->format('d/m/Y') }}
                                @endif
                            </td>
                            <td>
                                @if($override->is_open)
                                    <span class="badge badge-warning">Orario ridotto</span>
                                @else
                                    <span class="badge badge-danger">Chiusura</span>
                                @endif
                            </td>
                            <td>{{ $override->start_time ? substr($override->start_time, 0, 5) : '—' }}</td>
                            <td>{{ $override->end_time ? substr($override->end_time, 0, 5) : '—' }}</td>
                            <td>{{ $override->notes ?? '—' }}</td>
                            @if($canEdit)
                            <td class="text-right">
                                <button wire:click="startEditOverride({{ $override->id }})" class="btn btn-sm btn-primary mr-1" aria-label="Modifica eccezione">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button wire:click="deleteOverride({{ $override->id }})"
                                        wire:confirm="Eliminare questa eccezione?"
                                        class="btn btn-sm btn-danger"
                                        aria-label="Elimina eccezione">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                            @endif
                        </tr>
                        @endif
                    @empty
                    <tr>
                        <td colspan="{{ $canEdit ? 6 : 5 }}" class="text-center text-muted py-3">
                            Nessuna eccezione configurata.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
