<?php

namespace App\Livewire\Athlete;

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Profilo')]
class Profile extends Component
{
    // Dati profilo
    public string $name = '';

    public string $email = '';

    // Cambio password
    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    // Stato UI
    public string $activeSection = 'info';

    public string $profileMessage = '';

    public string $passwordMessage = '';

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    public function updateProfile(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->profileMessage = 'Profilo aggiornato.';
    }

    public function updatePassword(): void
    {
        try {
            $this->validate([
                'currentPassword' => ['required', 'string', 'current_password'],
                'newPassword' => ['required', 'string', Password::defaults(), 'confirmed:newPasswordConfirmation'],
                'newPasswordConfirmation' => ['required', 'string'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('currentPassword', 'newPassword', 'newPasswordConfirmation');
            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($this->newPassword),
        ]);

        $this->reset('currentPassword', 'newPassword', 'newPasswordConfirmation');
        $this->passwordMessage = 'Password aggiornata.';
    }

    public function deleteAccount(Logout $logout): void
    {
        $this->validate([
            'currentPassword' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: false);
    }

    public function render(): View
    {
        return view('livewire.athlete.profile')
            ->layout('layouts.athlete');
    }
}
