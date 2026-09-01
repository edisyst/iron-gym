<?php

namespace App\Livewire\Backoffice\Access;

use App\Models\AccessLog;
use App\Models\Member;
use App\Services\AccessService;
use App\Services\CheckinFailure;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class AccessLogList extends Component
{
    use WithPagination;

    public string $dateFilter = '';

    public string $search = '';

    public string $checkinSearch = '';

    public bool $showModal = false;

    public ?int $checkinMemberId = null;

    public string $checkinError = '';

    public function mount(): void
    {
        $this->dateFilter = today()->format('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openModal(): void
    {
        $this->checkinSearch = '';
        $this->checkinMemberId = null;
        $this->checkinError = '';
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function selectMember(int $memberId): void
    {
        $this->checkinMemberId = $memberId;
        $member = Member::find($memberId);
        $this->checkinSearch = $member ? "{$member->last_name} {$member->first_name}" : '';
        $this->checkinError = '';
    }

    public function registerAccess(): void
    {
        if (! $this->checkinMemberId) {
            $this->checkinError = 'Seleziona un tesserato.';

            return;
        }

        $member = Member::findOrFail($this->checkinMemberId);
        $result = app(AccessService::class)->checkin($member, auth()->id());

        if ($result->succeeded()) {
            session()->flash('success', 'Accesso registrato con successo.');
            $this->redirect('/backoffice/access-logs', navigate: false);

            return;
        }

        $this->checkinError = match ($result->failure) {
            CheckinFailure::MedicalCertInvalid => 'Certificato medico scaduto o mancante. Rinnovarlo prima di accedere alla struttura.',
            CheckinFailure::NoActiveSubscription => 'Il tesserato non ha un abbonamento attivo.',
            CheckinFailure::NoAccessesLeft => 'Il tesserato ha esaurito gli accessi disponibili.',
            null => throw new \LogicException('CheckinResult senza failure né successo.'),
        };
    }

    public function render(): View
    {
        $logs = AccessLog::with(['member', 'subscription.plan', 'checkedInBy'])
            ->when($this->dateFilter, fn ($q) => $q->whereDate('checked_in_at', $this->dateFilter))
            ->when($this->search, function ($q) {
                $q->whereHas('member', function ($q2) {
                    $q2->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%");
                });
            })
            ->orderByDesc('checked_in_at')
            ->paginate(15);

        $modalMembers = strlen($this->checkinSearch) >= 2
            ? Member::where('is_active', true)
                ->where(function ($q) {
                    $q->where('first_name', 'like', "%{$this->checkinSearch}%")
                        ->orWhere('last_name', 'like', "%{$this->checkinSearch}%");
                })
                ->orderBy('last_name')
                ->limit(10)
                ->get()
            : collect();

        return view('livewire.backoffice.access.access-log-list', [
            'logs' => $logs,
            'modalMembers' => $modalMembers,
        ])->layout('layouts.backoffice')
            ->layoutData(['page_title' => 'Registro accessi']);
    }
}
