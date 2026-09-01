<?php

namespace App\Livewire\Backoffice\Access;

use App\Models\AccessLog;
use App\Models\Member;
use App\Services\AccessService;
use App\Services\CheckinFailure;
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
        $result = app(AccessService::class)->checkin($member, auth()->id());

        if ($result->succeeded()) {
            $this->successMessage = "Accesso registrato per {$member->full_name}.";
            $this->search = '';
            $this->selectedMemberId = null;

            return;
        }

        $this->errorMessage = match ($result->failure) {
            CheckinFailure::MedicalCertInvalid => 'Certificato medico scaduto o mancante.',
            CheckinFailure::NoActiveSubscription => 'Nessun abbonamento attivo.',
            CheckinFailure::NoAccessesLeft => 'Accessi esauriti.',
            null => throw new \LogicException('CheckinResult senza failure né successo.'),
        };
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
