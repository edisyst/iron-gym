<?php

namespace App\Livewire\Backoffice\Messages;

use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class MessageThread extends Component
{
    public int $athleteId;

    public string $newMessage = '';

    public function mount(int $athleteId): void
    {
        $this->athleteId = $athleteId;

        // Segna come letti i messaggi non ancora letti
        Message::conversation(Auth::id(), $athleteId)
            ->where('receiver_id', Auth::id())
            ->unread()
            ->each(fn (Message $m) => $m->markAsRead());
    }

    public function sendMessage(): void
    {
        $this->validate(['newMessage' => 'required|string|max:2000'], [
            'newMessage.required' => 'Il messaggio non può essere vuoto.',
            'newMessage.max' => 'Il messaggio non può superare i 2000 caratteri.',
        ]);

        $athlete = User::role('atleta')->findOrFail($this->athleteId);

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $athlete->id,
            'body' => $this->newMessage,
        ]);

        $athlete->notify(new NewMessageNotification($message));

        $this->newMessage = '';
    }

    public function refresh(): void
    {
        // Segna come letti eventuali nuovi messaggi arrivati durante il polling
        Message::conversation(Auth::id(), $this->athleteId)
            ->where('receiver_id', Auth::id())
            ->unread()
            ->each(fn (Message $m) => $m->markAsRead());
    }

    public function render(): View
    {
        $messages = Message::conversation(Auth::id(), $this->athleteId)
            ->latest()
            ->take(100)
            ->get()
            ->reverse()
            ->values();
        $athlete = User::find($this->athleteId);

        return view('livewire.backoffice.messages.message-thread', compact('messages', 'athlete'))
            ->layout('layouts.backoffice')
            ->layoutData(['page_title' => 'Messaggi — '.($athlete->name ?? '')]);
    }
}
