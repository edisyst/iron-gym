{{--
    Toast stack globale — agganciato al layout atleta.
    Ascolta eventi:
      - `toast` (window): { message, type }  — type: success | error | info
      - `set-completed` (window): mostra "Set registrato" (dismiss rapido 2s)
    Il toast di rete è inviato da livewire:request-failed nel layout.
--}}
<div
    x-data="{
        queue: [],
        add(msg, type) {
            var id = Date.now() + Math.random();
            var ms = (type === 'set') ? 2000 : 3200;
            this.queue.push({ id: id, msg: msg, type: type || 'success' });
            var self = this;
            setTimeout(function () { self.remove(id); }, ms);
        },
        remove(id) {
            this.queue = this.queue.filter(function (t) { return t.id !== id; });
        }
    }"
    x-on:toast.window="add($event.detail.message, $event.detail.type || 'success')"
    x-on:set-completed.window="add('Set registrato', 'set')"
    class="ig-toast-stack"
    aria-live="polite"
    aria-atomic="false"
>
    <template x-for="t in queue" :key="t.id">
        <div
            class="ig-toast"
            :class="'ig-toast--' + t.type"
            x-transition:enter="ig-toast-enter"
            x-transition:enter-start="ig-toast-enter-from"
            x-transition:enter-end="ig-toast-enter-to"
            x-transition:leave="ig-toast-leave"
            x-transition:leave-start="ig-toast-leave-from"
            x-transition:leave-end="ig-toast-leave-to"
            role="alert"
        >
            <span x-text="t.msg" class="ig-toast__msg"></span>
            <button type="button" @click="remove(t.id)" class="ig-toast__close" aria-label="Chiudi">&times;</button>
        </div>
    </template>
</div>
