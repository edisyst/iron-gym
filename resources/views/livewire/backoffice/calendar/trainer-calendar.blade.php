{{-- Vista calendario prenotazioni backoffice --}}
<div>
    {{-- Toolbar --}}
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Calendario prenotazioni</h3>
            <div class="card-tools d-flex align-items-center gap-2">
                {{-- Selezione trainer (solo gestore vedrà altri trainer; trainer vede solo se stesso) --}}
                @if(count($trainers) > 1)
                    <select wire:model.live="selectedTrainerId" class="form-control form-control-sm mr-2" style="width:200px">
                        @foreach($trainers as $trainer)
                            <option value="{{ $trainer->id }}">{{ $trainer->name }}</option>
                        @endforeach
                    </select>
                @endif

                {{-- Navigazione settimana --}}
                <button wire:click="previousWeek" class="btn btn-sm btn-secondary">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span class="mx-2 font-weight-bold">
                    {{ \Carbon\Carbon::parse($weekStart)->format('d/m/Y') }}
                    &ndash;
                    {{ \Carbon\Carbon::parse($weekEnd)->format('d/m/Y') }}
                </span>
                <button wire:click="nextWeek" class="btn btn-sm btn-secondary">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
        <div class="card-body p-2">
            {{-- wire:ignore evita che Livewire demolisca FullCalendar al re-render --}}
            <div wire:ignore>
                <div id="calendar" style="min-height:600px"></div>
            </div>
        </div>
    </div>

    {{-- Modale creazione prenotazione PT --}}
    @if($showBookingModal)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white">Nuova prenotazione PT</h5>
                    <button wire:click="$set('showBookingModal', false)" class="close text-white">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Data</label>
                        <input type="text" class="form-control" value="{{ $selectedDate }}" readonly>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Ora inizio</label>
                                <input type="time" wire:model="bookingStart" class="form-control">
                                @error('bookingStart') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Ora fine</label>
                                <input type="time" wire:model="bookingEnd" class="form-control">
                                @error('bookingEnd') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Cerca tesserato</label>
                        <input type="text" wire:model.live="bookingMemberSearch"
                               placeholder="Nome o cognome..."
                               class="form-control mb-1">
                        <select wire:model="bookingMemberId" class="form-control" size="5">
                            <option value="0">-- seleziona --</option>
                            @foreach($members as $member)
                                <option value="{{ $member->id }}">{{ $member->full_name }}</option>
                            @endforeach
                        </select>
                        @error('bookingMemberId') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button wire:click="$set('showBookingModal', false)" class="btn btn-secondary">Annulla</button>
                    <button wire:click="createBooking" class="btn btn-primary">
                        <span wire:loading wire:target="createBooking" class="spinner-border spinner-border-sm mr-1"></span>
                        Prenota
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Modale dettaglio booking --}}
    @if($showDetailModal && $detailBooking)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        @if($detailType === 'pt') Prenotazione PT @else Corso collettivo @endif
                    </h5>
                    <button wire:click="$set('showDetailModal', false)" class="close">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if($detailType === 'pt')
                        <p><strong>Atleta:</strong> {{ $detailBooking->member?->full_name }}</p>
                        <p><strong>Data:</strong> {{ $detailBooking->booked_date->format('d/m/Y') }}</p>
                        <p><strong>Orario:</strong> {{ substr($detailBooking->start_time, 0, 5) }} &ndash; {{ substr($detailBooking->end_time, 0, 5) }}</p>
                        <p><strong>Status:</strong>
                            <span class="badge badge-{{ $detailBooking->status === 'confirmed' ? 'success' : 'warning' }}">
                                {{ $detailBooking->status }}
                            </span>
                        </p>
                        @if($detailBooking->notes)
                            <p><strong>Note:</strong> {{ $detailBooking->notes }}</p>
                        @endif
                    @elseif($detailType === 'class')
                        <p><strong>Corso:</strong> {{ $detailBooking->name }}</p>
                        <p><strong>Data:</strong> {{ $detailBooking->scheduled_at->format('d/m/Y H:i') }}</p>
                        <p><strong>Iscritti:</strong> {{ $detailBooking->confirmed_count }} / {{ $detailBooking->max_participants }}</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button wire:click="$set('showDetailModal', false)" class="btn btn-secondary">Chiudi</button>
                    @if($detailType === 'pt' && in_array($detailBooking->status, ['pending','confirmed']))
                        <button wire:click="cancelBooking({{ $detailBooking->id }})"
                                wire:confirm="Annullare questa prenotazione?"
                                class="btn btn-danger">
                            Annulla prenotazione
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('js')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        initialDate: '{{ $weekStart }}',
        slotMinTime: '07:00:00',
        slotMaxTime: '22:00:00',
        locale: 'it',
        headerToolbar: false,
        height: 'auto',
        selectable: true,
        selectMirror: true,
        events: @json($events),

        eventClick: function (info) {
            const props = info.event.extendedProps;
            if (props.type === 'pt') {
                @this.openDetailModal('pt', props.id);
            } else if (props.type === 'class') {
                @this.openDetailModal('class', props.id);
            }
        },

        select: function (info) {
            const dateStr = info.startStr.substring(0, 10);
            const startStr = info.startStr.substring(11, 16);
            const endStr = info.endStr.substring(11, 16);
            @this.openBookingModal(dateStr, startStr, endStr);
        },
    });

    calendar.render();

    // Riceve eventi aggiornati da PHP (navigazione settimana / cambio trainer)
    Livewire.on('calendar-refresh', function (payload) {
        const data = Array.isArray(payload) ? payload[0] : payload;
        calendar.getEventSources().forEach(function (s) { s.remove(); });
        calendar.addEventSource(data.events);
        calendar.gotoDate(data.weekStart);
    });

    Livewire.on('booking-created', function () {
        window.location.reload();
    });
    Livewire.on('booking-cancelled', function () {
        window.location.reload();
    });
});
</script>
@endpush
