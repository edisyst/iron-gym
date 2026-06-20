<?php

namespace App\Livewire\Backoffice\Shared;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class NotificationBell extends Component
{
    public function markAllAsRead(): void
    {
        Auth::user()?->unreadNotifications()->update(['read_at' => now()]);
    }

    public function markAsRead(string $id): void
    {
        DatabaseNotification::where('id', $id)
            ->where('notifiable_id', Auth::id())
            ->first()
            ?->markAsRead();
    }

    public function render(): View
    {
        $user = Auth::user();
        $unreadCount = $user?->unreadNotifications()->count() ?? 0;
        $notifications = $user?->notifications()->latest()->take(10)->get() ?? collect();

        return view('livewire.backoffice.shared.notification-bell', compact('unreadCount', 'notifications'));
    }
}
