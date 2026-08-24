<?php

namespace App\Livewire\Athlete;

use App\Livewire\Actions\Logout;
use App\Models\BodyMeasurement;
use App\Models\Member;
use App\Models\PersonalRecord;
use App\Models\PtBooking;
use App\Models\TrainingSession;
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
        $member = Member::where('user_id', Auth::id())->first();

        $subscription = $member?->subscriptions()
            ->with('plan')
            ->where('status', 'active')
            ->orderByDesc('expires_at')
            ->first();

        $upcomingPtBookings = collect();
        $pastPtBookings = collect();

        if ($member) {
            $upcomingPtBookings = PtBooking::with('trainer')
                ->where('member_id', $member->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->whereDate('booked_date', '>=', today())
                ->orderBy('booked_date')
                ->orderBy('start_time')
                ->get();

            $pastPtBookings = PtBooking::with('trainer')
                ->where('member_id', $member->id)
                ->whereIn('status', ['completed', 'no_show', 'cancelled'])
                ->whereDate('booked_date', '<', today())
                ->orderByDesc('booked_date')
                ->orderByDesc('start_time')
                ->limit(10)
                ->get();
        }

        $recentMeasurements = BodyMeasurement::where('athlete_id', Auth::id())
            ->orderByDesc('measured_at')
            ->limit(5)
            ->get();

        $recentPrs = PersonalRecord::with('exercise')
            ->where('athlete_id', Auth::id())
            ->where('record_type', 'e1rm')
            ->orderByDesc('achieved_at')
            ->limit(5)
            ->get();

        $recentSessions = TrainingSession::whereHas(
            'week.mesocycle',
            fn ($q) => $q->where('athlete_id', Auth::id())
        )
            ->whereIn('status', ['completed', 'skipped'])
            ->orderByDesc('completed_at')
            ->limit(5)
            ->get();

        return view('livewire.athlete.profile', compact('subscription', 'upcomingPtBookings', 'pastPtBookings', 'recentMeasurements', 'recentPrs', 'recentSessions'))
            ->layout('layouts.athlete');
    }
}
