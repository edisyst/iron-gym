<?php

namespace App\Livewire\Backoffice\Subscriptions;

use App\Models\Subscription;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class SubscriptionList extends Component
{
    use WithPagination;

    public string $filter = 'all';

    /** @var array<string, array<string, string>> */
    protected $queryString = ['filter' => ['except' => 'all']];

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        // Eager loading per evitare N+1 su member e plan
        $query = Subscription::with(['member', 'plan'])
            ->when($this->filter === 'active', fn ($q) => $q->active())
            ->when($this->filter === 'expired', fn ($q) => $q->where('status', 'expired'))
            ->when($this->filter === 'expiring', fn ($q) => $q->expiringSoon(30))
            ->orderByDesc('created_at');

        return view('livewire.backoffice.subscriptions.subscription-list', [
            'subscriptions' => $query->paginate(15),
        ])->layout('layouts.backoffice')
            ->layoutData(['page_title' => 'Abbonamenti']);
    }
}
