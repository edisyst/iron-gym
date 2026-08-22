<?php

namespace App\Livewire\Athlete;

use App\Models\Mesocycle;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class Messages extends Component
{
    public string $newMessage = '';

    public ?int $contactId = null;

    public function mount(): void
    {
        $trainerId = Mesocycle::where('athlete_id', Auth::id())
            ->whereIn('status', ['active', 'in_progress'])
            ->latest()
            ->value('trainer_id');

        $contactIds = $this->contactIds();

        if ($trainerId !== null && ! in_array($trainerId, $contactIds, true)) {
            $contactIds[] = $trainerId;
        }

        $this->contactId = $trainerId ?? ($contactIds[0] ?? null);

        $this->markCurrentThreadRead();
    }

    /** @return list<int> */
    private function contactIds(): array
    {
        $ids = Message::where('sender_id', Auth::id())->pluck('receiver_id')
            ->merge(Message::where('receiver_id', Auth::id())->pluck('sender_id'))
            ->unique()
            ->values()
            ->all();

        return array_map('intval', $ids);
    }

    private function markCurrentThreadRead(): void
    {
        if ($this->contactId === null) {
            return;
        }

        Message::conversation(Auth::id(), $this->contactId)
            ->where('receiver_id', Auth::id())
            ->unread()
            ->each(fn (Message $m) => $m->markAsRead());
    }

    public function selectContact(int $contactId): void
    {
        $this->contactId = $contactId;
        $this->markCurrentThreadRead();
    }

    public function sendMessage(): void
    {
        if ($this->contactId === null) {
            return;
        }

        $this->validate(['newMessage' => 'required|string|max:2000'], [
            'newMessage.required' => 'Il messaggio non può essere vuoto.',
        ]);

        $recipient = User::role(['trainer', 'gestore'])->findOrFail($this->contactId);

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $recipient->id,
            'body' => $this->newMessage,
        ]);
        $recipient->notify(new NewMessageNotification($message));

        $this->newMessage = '';
    }

    public function refresh(): void
    {
        $this->markCurrentThreadRead();
    }

    public function render(): View
    {
        $mesocycleTrainerId = Mesocycle::where('athlete_id', Auth::id())
            ->whereIn('status', ['active', 'in_progress'])
            ->latest()
            ->value('trainer_id');

        $contactIds = $this->contactIds();
        if ($mesocycleTrainerId !== null && ! in_array($mesocycleTrainerId, $contactIds, true)) {
            $contactIds[] = $mesocycleTrainerId;
        }

        $unreadByContact = Message::where('receiver_id', Auth::id())
            ->unread()
            ->select('sender_id', DB::raw('count(*) as c'))
            ->groupBy('sender_id')
            ->pluck('c', 'sender_id');

        $lastMessageByContact = Message::where('sender_id', Auth::id())
            ->orWhere('receiver_id', Auth::id())
            ->get()
            ->groupBy(fn (Message $m) => $m->sender_id === Auth::id() ? $m->receiver_id : $m->sender_id)
            ->map(fn ($group) => $group->sortByDesc('created_at')->first());

        $contacts = User::whereIn('id', $contactIds)
            ->get()
            ->map(function (User $user) use ($unreadByContact, $lastMessageByContact, $mesocycleTrainerId) {
                $lastMessage = $lastMessageByContact->get($user->id);

                return (object) [
                    'id' => $user->id,
                    'name' => $user->name,
                    'roleLabel' => $user->id === $mesocycleTrainerId
                        ? 'Trainer'
                        : ($user->hasRole('gestore') ? 'Gestore' : 'Trainer'),
                    'unreadCount' => $unreadByContact->get($user->id, 0),
                    'lastMessageAt' => $lastMessage?->created_at,
                    'lastMessageBody' => $lastMessage?->body,
                ];
            })
            ->sortByDesc('lastMessageAt')
            ->values();

        $contact = $this->contactId !== null ? User::find($this->contactId) : null;
        $messages = $this->contactId !== null
            ? Message::conversation(Auth::id(), $this->contactId)
                ->latest()
                ->take(100)
                ->get()
                ->reverse()
                ->values()
            : collect();

        $unreadCount = Message::where('receiver_id', Auth::id())->unread()->count();

        return view('livewire.athlete.messages', compact('contact', 'contacts', 'messages', 'unreadCount'))
            ->layout('layouts.athlete');
    }
}
