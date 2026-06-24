<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-7">
        <h1 class="text-2xl font-black tracking-tight text-white">Bentornato</h1>
        <p class="mt-1 text-sm text-white/40">Accedi al tuo account IronGym</p>
    </div>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form wire:submit="login" class="space-y-5">
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input wire:model="form.email" id="email" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" />
        </div>

        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input wire:model="form.password" id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('form.password')" />
        </div>

        <div class="flex items-center justify-between pt-1">
            <label for="remember" class="inline-flex items-center gap-2 cursor-pointer">
                <input wire:model="form.remember" id="remember" type="checkbox"
                       class="rounded border-white/20 bg-white/5 text-red-600 focus:ring-red-600 focus:ring-offset-0"
                       name="remember">
                <span class="text-sm text-white/50">Ricordami</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" wire:navigate
                   class="text-sm text-red-500 hover:text-red-400 transition-colors">
                    Password dimenticata?
                </a>
            @endif
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full">
                Accedi
            </x-primary-button>
        </div>

        @if (Route::has('register'))
            <p class="text-center text-sm text-white/35">
                Non hai un account?
                <a href="{{ route('register') }}" wire:navigate class="text-red-500 hover:text-red-400 transition-colors font-medium">
                    Registrati
                </a>
            </p>
        @endif
    </form>
</div>
