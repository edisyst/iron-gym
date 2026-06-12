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

    public string $filter = 'all';

    /** @var array<string, array<string, string>> */
    protected $queryString = [
        'search' => ['except' => ''],
        'filter' => ['except' => 'all'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        // Eager loading per evitare N+1 su activeSubscription e piano
        $query = Member::with(['activeSubscription.plan'])
            ->when($this->search, function ($q) {
                $q->where(function ($q2) {
                    $q2->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->filter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->filter === 'cert_issues', function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNull('medical_cert_expiry')
                        ->orWhere('medical_cert_expiry', '<=', now()->addDays(30)->toDateString());
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name');

        return view('livewire.backoffice.members.member-list', [
            'members' => $query->paginate(15),
        ])->layout('layouts.backoffice')
            ->layoutData(['page_title' => 'Tesserati']);
    }
}
