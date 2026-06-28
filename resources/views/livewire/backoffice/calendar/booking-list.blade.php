{{-- Lista prenotazioni PT con filtri e azioni --}}
<div>
    {{-- Filtri --}}
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Filtri</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label class="small">Data</label>
                    <input type="date" wire:model.live="filterDate" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="small">Trainer</label>
                    <select wire:model.live="filterTrainerId" class="form-control form-control-sm">
                        <option value="0">Tutti</option>
                        @foreach($trainers as $trainer)
                            <option value="{{ $trainer->id }}">{{ $trainer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small">Status</label>
                    <select wire:model.live="filterStatus" class="form-control form-control-sm">
                        <option value="">Tutti</option>
                        @foreach($statusLabels as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small">Cerca atleta</label>
                    <input type="text" wire:model.live.debounce.300ms="search"
                           placeholder="Nome o cognome..."
                           class="form-control form-control-sm">
                </div>
            </div>
        </div>
    </div>

    {{-- Tabella prenotazioni --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Data</th>
                            <th>Orario</th>
                            <th>Trainer</th>
                            <th>Atleta</th>
                            <th>Status</th>
                            <th>Deadline cancel.</th>
                            <th class="text-right">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                        <tr>
                            <td>{{ $booking->booked_date->format('d/m/Y') }}</td>
                            <td>{{ substr($booking->start_time, 0, 5) }} &ndash; {{ substr($booking->end_time, 0, 5) }}</td>
                            <td>{{ $booking->trainer?->name }}</td>
                            <td>{{ $booking->member?->full_name }}</td>
                            <td>
                                @php
                                    $badgeClass = match($booking->status) {
                                        'confirmed' => 'success',
                                        'pending'   => 'warning',
                                        'cancelled' => 'danger',
                                        'completed' => 'info',
                                        'no_show'   => 'secondary',
                                        default     => 'secondary',
                                    };
                                @endphp
                                <span class="badge badge-{{ $badgeClass }}">
                                    {{ $statusLabels[$booking->status] ?? $booking->status }}
                                </span>
                            </td>
                            <td>
                                @if($booking->cancellation_deadline)
                                    {{ $booking->cancellation_deadline->format('d/m H:i') }}
                                    @if($booking->canBeCancelledFree())
                                        <i class="fas fa-check-circle text-success ml-1" title="Gratuita"></i>
                                    @else
                                        <i class="fas fa-exclamation-circle text-warning ml-1" title="Fuori deadline"></i>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-right">
                                @if($booking->status === 'pending')
                                    <button wire:click="confirm({{ $booking->id }})"
                                            class="btn btn-xs btn-success mr-1" title="Conferma" aria-label="Conferma prenotazione">
                                        <i class="fas fa-check" aria-hidden="true"></i>
                                    </button>
                                @endif
                                @if(in_array($booking->status, ['pending', 'confirmed']))
                                    <button wire:click="openCancelModal({{ $booking->id }})"
                                            class="btn btn-xs btn-danger" title="Annulla" aria-label="Annulla prenotazione">
                                        <i class="fas fa-times" aria-hidden="true"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Nessuna prenotazione trovata.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($bookings->hasPages())
        <div class="card-footer">
            {{ $bookings->links() }}
        </div>
        @endif
    </div>

    {{-- Modale conferma annullamento --}}
    @if($showCancelModal)
    <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="modal-annulla-title" style="background:rgba(0,0,0,.5)">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white" id="modal-annulla-title">Annulla prenotazione</h5>
                    <button wire:click="$set('showCancelModal', false)" class="close text-white" aria-label="Chiudi">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Motivo dell'annullamento <span class="text-danger">*</span></label>
                        <textarea wire:model="cancelReason"
                                  class="form-control"
                                  rows="3"
                                  placeholder="Inserisci il motivo..."></textarea>
                        @error('cancelReason') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button wire:click="$set('showCancelModal', false)" class="btn btn-secondary">Annulla</button>
                    <button wire:click="cancel" class="btn btn-danger">
                        <span wire:loading wire:target="cancel" class="spinner-border spinner-border-sm mr-1"></span>
                        Conferma annullamento
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
