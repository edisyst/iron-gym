<?php

namespace App\Livewire\Backoffice\Members;

use App\Models\Member;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class MemberList extends Component
{
    use WithPagination;

    public function render(): View
    {
        $query = Member::with(['activeSubscription.plan'])
            ->orderBy('last_name')
            ->orderBy('first_name');

        return view('livewire.backoffice.members.member-list', [
            'members' => $query->paginate(15),
        ])->layout('layouts.backoffice')
            ->layoutData(['page_title' => 'Tesserati']);
    }
}
