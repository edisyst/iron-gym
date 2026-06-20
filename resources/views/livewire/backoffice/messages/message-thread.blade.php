<div wire:poll.3s="refresh">
    <div class="card card-primary card-outline" style="height: 65vh; display: flex; flex-direction: column;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-comments mr-2"></i>
                Conversazione con {{ $athlete?->name ?? 'Atleta' }}
            </h3>
        </div>

        {{-- Area messaggi scrollabile --}}
        <div
            class="card-body p-3"
            id="messages-area"
            style="flex: 1; overflow-y: auto;"
            x-data
            x-init="$el.scrollTop = $el.scrollHeight"
            x-on:livewire:update.window="setTimeout(() => $el.scrollTop = $el.scrollHeight, 50)"
        >
            @forelse ($messages as $message)
                @php $isMine = $message->sender_id === auth()->id(); @endphp
                <div class="d-flex mb-3 {{ $isMine ? 'justify-content-end' : 'justify-content-start' }}">
                    <div
                        class="px-3 py-2 rounded shadow-sm"
                        style="
                            max-width: 70%;
                            background-color: {{ $isMine ? '#007bff' : '#f4f6f9' }};
                            color: {{ $isMine ? '#fff' : '#333' }};
                        "
                    >
                        <div class="text-sm">{{ $message->body }}</div>
                        <div
                            class="text-xs mt-1"
                            style="opacity: 0.75; text-align: {{ $isMine ? 'right' : 'left' }};"
                        >
                            {{ $message->created_at->format('d/m H:i') }}
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-muted text-center mt-4">Nessun messaggio ancora. Scrivi il primo!</p>
            @endforelse
        </div>

        {{-- Input --}}
        <div class="card-footer p-2">
            <form wire:submit="sendMessage" class="d-flex gap-2">
                <input
                    wire:model="newMessage"
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="Scrivi un messaggio..."
                    autocomplete="off"
                    @keydown.enter.prevent="$wire.sendMessage()"
                >
                <button type="submit" class="btn btn-primary btn-sm ml-2">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
            @error('newMessage')
                <span class="text-danger text-xs">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>
