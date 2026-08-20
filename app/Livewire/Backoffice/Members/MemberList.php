<?php

namespace App\Livewire\Backoffice\Members;

use App\Models\Member;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class MemberList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $certFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCertFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Member::with(['activeSubscription.plan'])
            ->when($this->search, function ($q) {
                $q->where(function ($q2) {
                    $q2->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->certFilter, function ($q) {
                match ($this->certFilter) {
                    'missing' => $q->whereNull('medical_cert_expiry'),
                    'expired' => $q->whereNotNull('medical_cert_expiry')->where('medical_cert_expiry', '<', now()),
                    'expiring_soon' => $q->whereNotNull('medical_cert_expiry')
                        ->whereBetween('medical_cert_expiry', [now(), now()->addDays(30)]),
                    default => null,
                };
            })
            ->orderBy('last_name')
            ->orderBy('first_name');

        return view('livewire.backoffice.members.member-list', [
            'members' => $query->paginate(15),
        ])->layout('layouts.backoffice')
            ->layoutData(['page_title' => 'Tesserati']);
    }
}
