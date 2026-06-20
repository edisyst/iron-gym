{{-- Gestione disponibilità trainer: slot ricorrenti e override puntuali --}}
<div>
    {{-- ============================================================ --}}
    {{-- Slot ricorrenti settimanali --}}
    {{-- ============================================================ --}}
    <div class="card card-outline card-success">
        <div class="card-header">
            <h3 class="card-title">Disponibilità settimanale</h3>
            <div class="card-tools">
                <button wire:click="$toggle('showAddSlot')" class="btn btn-sm btn-success">
                    <i class="fas fa-plus mr-1"></i> Aggiungi slot
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            {{-- Form aggiunta slot --}}
            @if($showAddSlot)
            <div class="p-3 border-bottom bg-light">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="small">Giorno</label>
                        <select wire:model="newDayOfWeek" class="form-control form-control-sm">
                            @foreach($daysOfWeek as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('newDayOfWeek') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="small">Ora inizio</label>
                        <input type="time" wire:model="newStartTime" class="form-control form-control-sm">
                        @error('newStartTime') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="small">Ora fine</label>
                        <input type="time" wire:model="newEndTime" class="form-control form-control-sm">
                        @error('newEndTime') <span class="text-danger small">{{ $message }}</span> @enderror
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
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($slots as $slot)
                    <tr>
                        <td>{{ $daysOfWeek[$slot->day_of_week] ?? $slot->day_of_week }}</td>
                        <td>{{ substr($slot->start_time, 0, 5) }}</td>
                        <td>{{ substr($slot->end_time, 0, 5) }}</td>
                        <td class="text-right">
                            <button wire:click="deleteSlot({{ $slot->id }})"
                                    wire:confirm="Eliminare questo slot?"
                                    class="btn btn-xs btn-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-3">
                            Nessuno slot ricorrente configurato.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- Override / eccezioni puntuali --}}
    {{-- ============================================================ --}}
    <div class="card card-outline card-warning">
        <div class="card-header">
            <h3 class="card-title">Eccezioni / date specifiche</h3>
            <div class="card-tools">
                <button wire:click="$toggle('showAddOverride')" class="btn btn-sm btn-warning">
                    <i class="fas fa-plus mr-1"></i> Aggiungi eccezione
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            {{-- Form aggiunta override --}}
            @if($showAddOverride)
            <div class="p-3 border-bottom bg-light">
                <div class="row align-items-end">
                    <div class="col-md-2">
                        <label class="small">Data</label>
                        <input type="date" wire:model="newDate" class="form-control form-control-sm">
                        @error('newDate') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="small">Dalle</label>
                        <input type="time" wire:model="newOverrideStart" class="form-control form-control-sm">
                        @error('newOverrideStart') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="small">Alle</label>
                        <input type="time" wire:model="newOverrideEnd" class="form-control form-control-sm">
                        @error('newOverrideEnd') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="small">Tipo</label>
                        <select wire:model="newIsAvailable" class="form-control form-control-sm">
                            <option value="1">Apertura straordinaria</option>
                            <option value="0">Blocco / chiusura</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small">Note</label>
                        <input type="text" wire:model="newNotes" class="form-control form-control-sm"
                               placeholder="Opzionale">
                    </div>
                    <div class="col-md-2">
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
                        <th>Dalle</th>
                        <th>Alle</th>
                        <th>Tipo</th>
                        <th>Note</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($overrides as $override)
                    <tr>
                        <td>{{ $override->specific_date->format('d/m/Y') }}</td>
                        <td>{{ substr($override->start_time, 0, 5) }}</td>
                        <td>{{ substr($override->end_time, 0, 5) }}</td>
                        <td>
                            @if($override->is_available)
                                <span class="badge badge-success">Apertura</span>
                            @else
                                <span class="badge badge-danger">Blocco</span>
                            @endif
                        </td>
                        <td>{{ $override->notes ?? '—' }}</td>
                        <td class="text-right">
                            <button wire:click="deleteOverride({{ $override->id }})"
                                    wire:confirm="Eliminare questa eccezione?"
                                    class="btn btn-xs btn-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-3">
                            Nessuna eccezione configurata.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
