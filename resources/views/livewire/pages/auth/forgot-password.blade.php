<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        $this->reset('email');

        session()->flash('status', __($status));
    }
}; ?>

<div>
    <div class="mb-7">
        <h1 class="text-2xl font-black tracking-tight text-white">Password dimenticata?</h1>
        <p class="mt-2 text-sm text-white/40 leading-relaxed">
            Inserisci l'email associata al tuo account e ti invieremo un link per reimpostare la password.
        </p>
    </div>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink" class="space-y-5">
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input wire:model="email" id="email" type="email" name="email" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full">
                Invia link di reset
            </x-primary-button>
        </div>

        <p class="text-center text-sm text-white/35">
            <a href="{{ route('login') }}" wire:navigate class="text-red-500 hover:text-red-400 transition-colors">
                Torna al login
            </a>
        </p>
    </form>
</div>
