<div wire:poll.3s="refresh" class="athlete-card" style="display: flex; flex-direction: column; height: calc(100vh - 160px);">

    @if ($trainer === null)
        <div style="color: #888; text-align: center; margin: auto;">
            <p>Nessun trainer assegnato.</p>
            <p style="font-size: 13px;">Contatta la palestra per attivare un mesociclo.</p>
        </div>
    @else
        {{-- Header --}}
        <div style="padding-bottom: 12px; border-bottom: 1px solid #2A2A2A; margin-bottom: 12px;">
            <div style="font-weight: 600;">{{ $trainer->name }}</div>
            <div style="font-size: 12px; color: #888;">Il tuo trainer</div>
        </div>

        {{-- Area messaggi --}}
        <div
            id="athlete-messages"
            style="flex: 1; overflow-y: auto; padding-right: 4px;"
            x-data
            x-init="$el.scrollTop = $el.scrollHeight"
            x-on:livewire:update.window="setTimeout(() => $el.scrollTop = $el.scrollHeight, 50)"
        >
            @forelse ($messages as $message)
                @php $isMine = $message->sender_id === auth()->id(); @endphp
                <div
                    style="
                        display: flex;
                        justify-content: {{ $isMine ? 'flex-end' : 'flex-start' }};
                        margin-bottom: 10px;
                    "
                >
                    <div
                        style="
                            max-width: 75%;
                            padding: 10px 14px;
                            border-radius: 12px;
                            background-color: {{ $isMine ? '#FF6B00' : '#2A2A2A' }};
                            color: #fff;
                            font-size: 14px;
                        "
                    >
                        <div>{{ $message->body }}</div>
                        <div style="font-size: 11px; opacity: 0.7; margin-top: 4px; text-align: {{ $isMine ? 'right' : 'left' }};">
                            {{ $message->created_at->format('d/m H:i') }}
                        </div>
                    </div>
                </div>
            @empty
                <x-athlete.empty-state title="Nessun messaggio"
                    body="Scrivi il primo messaggio al tuo trainer." />
            @endforelse
        </div>

        {{-- Input --}}
        <div style="padding-top: 12px; border-top: 1px solid #2A2A2A; margin-top: 12px;">
            <form wire:submit="sendMessage" style="display: flex; gap: 8px;">
                <input
                    wire:model="newMessage"
                    type="text"
                    class="workout-input"
                    style="flex: 1; width: auto; text-align: left; padding: 10px 14px;"
                    placeholder="Scrivi un messaggio..."
                    autocomplete="off"
                >
                <button type="submit" class="btn-accent" style="width: auto; padding: 10px 16px;"
                        wire:loading.attr="disabled">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </form>
            @error('newMessage') <span class="ig-field-error">{{ $message }}</span> @enderror
        </div>
    @endif
</div>
