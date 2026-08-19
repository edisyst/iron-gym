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

    /** @var array<string, array<string, string>> */
    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch(): void
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
            ->orderBy('last_name')
            ->orderBy('first_name');

        return view('livewire.backoffice.members.member-list', [
            'members' => $query->paginate(15),
        ])->layout('layouts.backoffice')
            ->layoutData(['page_title' => 'Tesserati']);
    }
}
