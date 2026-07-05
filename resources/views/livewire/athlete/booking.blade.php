{{-- Prenotazioni atleta: sessioni PT e corsi collettivi --}}
<div>
    {{-- Flash messages --}}
    @if(session('success'))
    <div style="background:#22c55e;color:#fff;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:14px;">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div style="background:#ef4444;color:#fff;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:14px;">
        {{ session('error') }}
    </div>
    @endif

    {{-- Tab switcher --}}
    <div class="ig-tab-group">
        <button wire:click="$set('activeTab','pt')"
                class="ig-tab {{ $activeTab === 'pt' ? 'ig-tab--active' : '' }}">
            Sessione PT
        </button>
        <button wire:click="$set('activeTab','classes')"
                class="ig-tab {{ $activeTab === 'classes' ? 'ig-tab--active' : '' }}">
            Corsi
        </button>
    </div>

    {{-- ============================================================ --}}
    {{-- Tab PT --}}
    {{-- ============================================================ --}}
    @if($activeTab === 'pt')
    <div>
        <div class="athlete-card">
            <p class="section-title">Prenota sessione PT</p>

            {{-- Select trainer --}}
            <div style="margin-bottom:12px;">
                <label style="color:#888;font-size:12px;display:block;margin-bottom:4px;">Trainer</label>
                <select wire:model.live="selectedTrainerId"
                        style="width:100%;background:#2A2A2A;border:1px solid #333;border-radius:8px;color:#fff;padding:10px;font-size:15px;">
                    <option value="0">-- seleziona trainer --</option>
                    @foreach($trainers as $trainer)
                        <option value="{{ $trainer->id }}">{{ $trainer->name }}</option>
                    @endforeach
                </select>
                @error('selectedTrainerId') <span class="ig-field-error">{{ $message }}</span> @enderror
            </div>

            {{-- Date picker --}}
            <div style="margin-bottom:12px;">
                <label style="color:#888;font-size:12px;display:block;margin-bottom:4px;">Data</label>
                <input type="date"
                       wire:model.live="selectedDate"
                       min="{{ now()->toDateString() }}"
                       style="width:100%;background:#2A2A2A;border:1px solid #333;border-radius:8px;color:#fff;padding:10px;font-size:15px;">
                @error('selectedDate') <span class="ig-field-error">{{ $message }}</span> @enderror
            </div>

            {{-- Griglia slot disponibili --}}
            @if($selectedTrainerId > 0 && $selectedDate)
                <div style="margin-bottom:12px;">
                    <label style="color:#888;font-size:12px;display:block;margin-bottom:8px;">
                        Slot disponibili
                        <span wire:loading wire:target="loadAvailableSlots,updatedSelectedDate,updatedSelectedTrainerId"
                              style="color:#FF6B00;margin-left:6px;">...</span>
                    </label>

                    @error('selectedStart') <span class="ig-field-error" style="margin-bottom:8px;">{{ $message }}</span> @enderror
                    @if($availableSlots->isEmpty())
                        <x-athlete.empty-state title="Nessuno slot disponibile"
                            body="Prova un'altra data o un altro trainer." />
                    @else
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
                            @foreach($availableSlots as $slot)
                            <button wire:click="selectSlot('{{ $slot['start'] }}', '{{ $slot['end'] }}')"
                                    style="padding:10px 6px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:.15s;
                                           {{ $selectedStart === $slot['start'] ? 'background:#FF6B00;color:#fff;border:2px solid #FF6B00;' : 'background:#2A2A2A;color:#ccc;border:2px solid #333;' }}">
                                {{ $slot['start'] }}&ndash;{{ $slot['end'] }}
                            </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            {{-- Bottone prenota --}}
            @if($selectedStart)
            <button wire:click="bookPt"
                    wire:loading.attr="disabled"
                    style="width:100%;background:#FF6B00;color:#fff;border:none;border-radius:8px;padding:14px;font-size:16px;font-weight:700;cursor:pointer;margin-top:8px;">
                <span wire:loading wire:target="bookPt">Prenotazione...</span>
                <span wire:loading.remove wire:target="bookPt">Prenota {{ $selectedStart }}&ndash;{{ $selectedEnd }}</span>
            </button>
            @endif
        </div>

        {{-- Lista prenotazioni PT future --}}
        @if($futurePtBookings->isNotEmpty())
        <div class="section-title" style="margin-top:8px;">Le tue prenotazioni PT</div>
        @foreach($futurePtBookings as $booking)
        <div class="athlete-card" style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div style="font-weight:600;font-size:15px;">
                    {{ $booking->booked_date->format('d/m/Y') }}
                    &mdash; {{ substr($booking->start_time, 0, 5) }}&ndash;{{ substr($booking->end_time, 0, 5) }}
                </div>
                <div style="color:#888;font-size:13px;margin-top:2px;">
                    Trainer: {{ $booking->trainer?->name }}
                </div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
                <span class="athlete-badge {{ $booking->status === 'confirmed' ? 'badge-green' : 'badge-gray' }}">
                    {{ $booking->status === 'confirmed' ? 'Confermata' : 'In attesa' }}
                </span>
                @if($booking->canBeCancelledFree())
                <button wire:click="cancelPtBooking({{ $booking->id }})"
                        wire:confirm="Annullare questa prenotazione?"
                        class="btn-ghost" style="font-size:12px;padding:4px 10px;">
                    Annulla
                </button>
                @endif
            </div>
        </div>
        @endforeach
        @endif
    </div>
    @endif

    {{-- ============================================================ --}}
    {{-- Tab Corsi --}}
    {{-- ============================================================ --}}
    @if($activeTab === 'classes')
    <div>
        <p class="section-title">Corsi disponibili</p>

        @forelse($futureClasses as $class)
        @php
            $alreadyEnrolled = in_array($class->id, $myEnrolledClassIds);
            $myBookingForClass = $myClassBookings->firstWhere('class_id', $class->id);
        @endphp
        <div class="athlete-card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                <div>
                    <div style="font-weight:700;font-size:16px;">{{ $class->name }}</div>
                    <div style="color:#888;font-size:13px;margin-top:2px;">
                        {{ $class->scheduled_at->format('d/m/Y H:i') }}
                        &mdash; {{ $class->duration_minutes }} min
                    </div>
                    <div style="color:#888;font-size:13px;">Trainer: {{ $class->trainer?->name }}</div>
                </div>
                <div style="text-align:right;">
                    @if($class->is_full)
                        <span class="athlete-badge badge-red">PIENO</span>
                    @else
                        <span class="athlete-badge badge-green">{{ $class->available_spots }} posti</span>
                    @endif
                </div>
            </div>

            @if($class->description)
            <p style="color:#aaa;font-size:13px;margin-bottom:10px;">{{ $class->description }}</p>
            @endif

            @if($alreadyEnrolled && $myBookingForClass)
                {{-- Già iscritto --}}
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    @if($myBookingForClass->status === 'waitlisted')
                        <span class="athlete-badge badge-gray">
                            Lista d'attesa #{{ $myBookingForClass->position }}
                        </span>
                    @else
                        <span class="athlete-badge badge-green">Iscritto</span>
                    @endif
                    <button wire:click="cancelClassBooking({{ $myBookingForClass->id }})"
                            wire:confirm="Annullare l'iscrizione?"
                            class="btn-ghost" style="font-size:12px;padding:4px 10px;">
                        Annulla iscrizione
                    </button>
                </div>
            @else
                {{-- Non ancora iscritto --}}
                <button wire:click="enrollClass({{ $class->id }})"
                        class="btn-accent" style="padding:10px;">
                    <span wire:loading wire:target="enrollClass({{ $class->id }})">Iscrizione...</span>
                    <span wire:loading.remove wire:target="enrollClass({{ $class->id }})">
                        {{ $class->is_full ? "Iscriviti alla lista d'attesa" : 'Iscriviti' }}
                    </span>
                </button>
            @endif
        </div>
        @empty
        <x-athlete.card>
            <x-athlete.empty-state title="Nessun corso disponibile"
                body="Non ci sono corsi programmati al momento." />
        </x-athlete.card>
        @endforelse

        {{-- Le mie iscrizioni attive --}}
        @if($myClassBookings->isNotEmpty())
        <p class="section-title" style="margin-top:16px;">Le mie iscrizioni</p>
        @foreach($myClassBookings as $booking)
        <div class="athlete-card" style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div style="font-weight:600;font-size:15px;">{{ $booking->groupClass?->name }}</div>
                <div style="color:#888;font-size:13px;">
                    {{ $booking->groupClass?->scheduled_at->format('d/m/Y H:i') }}
                </div>
            </div>
            <span class="athlete-badge {{ $booking->status === 'confirmed' ? 'badge-green' : 'badge-gray' }}">
                @if($booking->status === 'waitlisted')
                    Attesa #{{ $booking->position }}
                @else
                    Confermato
                @endif
            </span>
        </div>
        @endforeach
        @endif
    </div>
    @endif
</div>
