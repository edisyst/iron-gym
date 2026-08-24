<?php

namespace App\Livewire\Athlete;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Notifications extends Component
{
    use WithPagination;

    public function markRead(string $id): void
    {
        DatabaseNotification::where('id', $id)
            ->where('notifiable_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function markAllRead(): void
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);
    }

    public function deleteNotification(string $id): void
    {
        DatabaseNotification::where('id', $id)
            ->where('notifiable_id', Auth::id())
            ->delete();
    }

    public function render(): View
    {
        $notifications = Auth::user()
            ->notifications()
            ->latest()
            ->paginate(20);

        $unreadCount = Auth::user()->unreadNotifications()->count();

        return view('livewire.athlete.notifications', compact('notifications', 'unreadCount'))
            ->layout('layouts.athlete');
    }
}
