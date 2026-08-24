<?php

namespace App\Livewire\Backoffice\Access;

use App\Models\AccessLog;
use App\Models\Member;
use App\Models\Subscription;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Check-in Rapido')]
class QuickCheckin extends Component
{
    public string $search = '';

    public ?int $selectedMemberId = null;

    public string $successMessage = '';

    public string $errorMessage = '';

    public function updatingSearch(): void
    {
        $this->selectedMemberId = null;
        $this->successMessage = '';
        $this->errorMessage = '';
    }

    public function selectMember(int $memberId): void
    {
        $this->selectedMemberId = $memberId;
        $member = Member::find($memberId);
        $this->search = $member ? "{$member->last_name} {$member->first_name}" : '';
        $this->successMessage = '';
        $this->errorMessage = '';
    }

    public function registerAccess(): void
    {
        if (! $this->selectedMemberId) {
            $this->errorMessage = 'Seleziona un tesserato dalla lista.';

            return;
        }

        $member = Member::findOrFail($this->selectedMemberId);

        if (! $member->has_medical_cert_valid) {
            $this->errorMessage = 'Certificato medico scaduto o mancante.';

            return;
        }

        $subscription = Subscription::where('member_id', $this->selectedMemberId)
            ->active()
            ->first();

        if (! $subscription) {
            $this->errorMessage = 'Nessun abbonamento attivo.';

            return;
        }

        if ($subscription->accesses_remaining !== null && $subscription->accesses_remaining <= 0) {
            $this->errorMessage = 'Accessi esauriti.';

            return;
        }

        $subscription->increment('accesses_used');
        if ($subscription->accesses_remaining !== null) {
            $subscription->decrement('accesses_remaining');
        }

        AccessLog::create([
            'member_id' => $this->selectedMemberId,
            'subscription_id' => $subscription->id,
            'checked_in_at' => now(),
            'checked_in_by' => auth()->id(),
        ]);

        $this->successMessage = "Accesso registrato per {$member->full_name}.";
        $this->search = '';
        $this->selectedMemberId = null;
    }

    /** @return Collection<int, Member> */
    private function searchResults(): Collection
    {
        if (strlen($this->search) < 2 || $this->selectedMemberId) {
            return collect();
        }

        return Member::with(['activeSubscription.plan'])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            })
            ->orderBy('last_name')
            ->limit(8)
            ->get();
    }

    public function render(): View
    {
        $todayLogs = AccessLog::with(['member', 'subscription.plan', 'checkedInBy'])
            ->whereDate('checked_in_at', today())
            ->orderByDesc('checked_in_at')
            ->limit(10)
            ->get();

        return view('livewire.backoffice.access.quick-checkin', [
            'searchResults' => $this->searchResults(),
            'todayLogs' => $todayLogs,
        ])->layout('layouts.backoffice')
            ->layoutData(['page_title' => 'Check-in Rapido']);
    }
}
