<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <div class="mb-7">
        <h1 class="text-2xl font-black tracking-tight text-white">Verifica email</h1>
        <p class="mt-2 text-sm text-white/40 leading-relaxed">
            Grazie per esserti registrato! Prima di iniziare, verifica il tuo indirizzo email cliccando sul link che ti abbiamo inviato.
            Se non hai ricevuto l'email, puoi richiederne un'altra.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-5 rounded-lg border border-green-500/20 bg-green-500/10 px-4 py-2.5 text-sm text-green-400">
            Un nuovo link di verifica è stato inviato all'indirizzo email fornito durante la registrazione.
        </div>
    @endif

    <div class="space-y-4">
        <x-primary-button wire:click="sendVerification" class="w-full">
            Reinvia email di verifica
        </x-primary-button>

        <button wire:click="logout" type="button"
                class="w-full text-sm text-white/35 hover:text-white/60 transition-colors py-2">
            Esci
        </button>
    </div>
</div>
