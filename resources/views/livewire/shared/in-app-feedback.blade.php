<div>
    {{-- Bottone flottante --}}
    <button
        wire:click="$set('open', true)"
        style="
            position: fixed;
            bottom: 80px;
            right: 20px;
            z-index: 9999;
            background: #FF6B00;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.25);
        "
        title="Invia feedback"
    >
        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
    </button>

    {{-- Modal feedback --}}
    @if ($open)
    <div
        style="
            position: fixed;
            bottom: 140px;
            right: 20px;
            z-index: 9999;
            background: #1E1E1E;
            border: 1px solid #2A2A2A;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.5);
            width: 320px;
            padding: 20px;
            color: #fff;
        "
    >
        <h6 style="margin: 0 0 14px; font-weight: 700; color: #fff;">Invia feedback</h6>

        @if (session('feedback_sent'))
            <div style="background:#22c55e22; border:1px solid #22c55e; color:#22c55e; border-radius:6px; padding:8px 12px; font-size:13px;">Grazie, feedback inviato!</div>
        @else
        <form wire:submit="submit">
            <div style="margin-bottom: 12px;">
                <label style="display:block; font-size:13px; margin-bottom:6px; font-weight:600; color:#aaa;">Tipo</label>
                <div style="display:flex; gap:12px;">
                    <label style="font-size:13px; cursor:pointer; color:#ccc; display:flex; align-items:center; gap:4px;">
                        <input type="radio" wire:model="type" value="bug"> Bug
                    </label>
                    <label style="font-size:13px; cursor:pointer; color:#ccc; display:flex; align-items:center; gap:4px;">
                        <input type="radio" wire:model="type" value="suggestion"> Suggerimento
                    </label>
                    <label style="font-size:13px; cursor:pointer; color:#ccc; display:flex; align-items:center; gap:4px;">
                        <input type="radio" wire:model="type" value="confused"> Confuso su…
                    </label>
                </div>
                @error('type') <div style="color:#ef4444; font-size:12px; margin-top:4px;">{{ $message }}</div> @enderror
            </div>

            <input type="hidden" wire:model="pageUrl" id="feedback-page-url">

            <div style="margin-bottom: 14px;">
                <label style="display:block; font-size:13px; margin-bottom:6px; font-weight:600; color:#aaa;">Descrizione</label>
                <textarea
                    wire:model="body"
                    maxlength="500"
                    rows="4"
                    style="width:100%; background:#2A2A2A; border:1px solid #333; border-radius:6px; padding:8px 10px; font-size:13px; resize:vertical; color:#fff; outline:none;"
                    placeholder="Descrivi il problema o il suggerimento…"
                ></textarea>
                @error('body') <div style="color:#ef4444; font-size:12px; margin-top:4px;">{{ $message }}</div> @enderror
            </div>

            <div style="display:flex; gap:8px; justify-content:flex-end;">
                <button type="button" wire:click="$set('open', false)"
                        style="background:#2A2A2A; border:1px solid #444; border-radius:6px; padding:7px 16px; font-size:13px; cursor:pointer; color:#aaa;">
                    Annulla
                </button>
                <button type="submit"
                        style="background:#FF6B00; color:#fff; border:none; border-radius:6px; padding:7px 16px; font-size:13px; cursor:pointer; font-weight:600;">
                    Invia
                </button>
            </div>
        </form>
        @endif
    </div>
    @endif

    <script>
        document.addEventListener('livewire:init', function () {
            var el = document.getElementById('feedback-page-url');
            if (el) el.value = window.location.pathname;
        });
    </script>
</div>
