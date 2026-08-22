<div wire:poll.3s="refresh" class="athlete-card" style="display: flex; flex-direction: column; height: calc(100vh - 160px);">

    @if ($contacts->isEmpty())
        <div style="color: #888; text-align: center; margin: auto;">
            <p>Nessun trainer assegnato.</p>
            <p style="font-size: 13px;">Contatta la palestra per attivare un mesociclo.</p>
        </div>
    @else
        {{-- Tabs contatti --}}
        @if ($contacts->count() > 1)
            <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 12px; margin-bottom: 12px; border-bottom: 1px solid #2A2A2A;">
                @foreach ($contacts as $c)
                    <button
                        type="button"
                        wire:click="selectContact({{ $c->id }})"
                        style="
                            flex: 0 0 auto;
                            position: relative;
                            padding: 8px 14px;
                            border-radius: 20px;
                            border: 1px solid {{ $contact && $contact->id === $c->id ? '#FF6B00' : '#2A2A2A' }};
                            background-color: {{ $contact && $contact->id === $c->id ? '#FF6B00' : 'transparent' }};
                            color: {{ $contact && $contact->id === $c->id ? '#fff' : '#ccc' }};
                            font-size: 13px;
                            white-space: nowrap;
                        "
                    >
                        {{ $c->name }}
                        <span style="opacity: 0.7;">({{ $c->roleLabel }})</span>
                        @if ($c->unreadCount > 0)
                            <span style="
                                position: absolute;
                                top: -4px;
                                right: -4px;
                                background-color: #E85D04;
                                color: #fff;
                                border-radius: 50%;
                                min-width: 16px;
                                height: 16px;
                                font-size: 10px;
                                line-height: 16px;
                                text-align: center;
                                padding: 0 3px;
                            ">{{ $c->unreadCount }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
        @endif

        {{-- Header --}}
        <div style="padding-bottom: 12px; border-bottom: 1px solid #2A2A2A; margin-bottom: 12px;">
            <div style="font-weight: 600;">{{ $contact->name }}</div>
            <div style="font-size: 12px; color: #888;">
                {{ $contacts->firstWhere('id', $contact->id)?->roleLabel }}
            </div>
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
                    body="Scrivi il primo messaggio a {{ $contact->name }}." />
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
