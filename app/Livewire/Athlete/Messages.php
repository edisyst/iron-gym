<?php

namespace App\Livewire\Athlete;

use App\Models\Mesocycle;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Messages extends Component
{
    public string $newMessage = '';

    private ?int $trainerId = null;

    public function mount(): void
    {
        $mesocycle = Mesocycle::where('athlete_id', Auth::id())
            ->whereIn('status', ['active', 'in_progress'])
            ->latest()
            ->first();

        $this->trainerId = $mesocycle?->trainer_id;

        if ($this->trainerId !== null) {
            Message::conversation(Auth::id(), $this->trainerId)
                ->where('receiver_id', Auth::id())
                ->unread()
                ->each(fn (Message $m) => $m->markAsRead());
        }
    }

    public function sendMessage(): void
    {
        if ($this->trainerId === null) {
            return;
        }

        $this->validate(['newMessage' => 'required|string|max:2000'], [
            'newMessage.required' => 'Il messaggio non può essere vuoto.',
        ]);

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $this->trainerId,
            'body' => $this->newMessage,
        ]);

        $trainer = User::find($this->trainerId);
        $trainer?->notify(new NewMessageNotification($message));

        $this->newMessage = '';
    }

    public function refresh(): void
    {
        if ($this->trainerId !== null) {
            Message::conversation(Auth::id(), $this->trainerId)
                ->where('receiver_id', Auth::id())
                ->unread()
                ->each(fn (Message $m) => $m->markAsRead());
        }
    }

    public function render(): View
    {
        $trainer = $this->trainerId !== null ? User::find($this->trainerId) : null;
        $messages = $this->trainerId !== null
            ? Message::conversation(Auth::id(), $this->trainerId)->get()
            : collect();

        $unreadCount = Message::where('receiver_id', Auth::id())->unread()->count();

        return view('livewire.athlete.messages', compact('trainer', 'messages', 'unreadCount'))
            ->layout('layouts.athlete');
    }
}
