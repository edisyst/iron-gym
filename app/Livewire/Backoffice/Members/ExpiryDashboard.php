<?php

namespace App\Livewire\Backoffice\Members;

use App\Models\Member;
use App\Models\Subscription;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Scadenze')]
class ExpiryDashboard extends Component
{
    public int $certDays = 30;

    public int $subDays = 7;

    public string $search = '';

    public function render(): View
    {
        $certQuery = Member::with(['activeSubscription.plan'])
            ->whereNotNull('medical_cert_expiry')
            ->where('medical_cert_expiry', '<=', now()->addDays($this->certDays)->toDateString())
            ->when($this->search, fn ($q) => $q->where(function ($q2) {
                $q2->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->orderBy('medical_cert_expiry')
            ->get();

        $subQuery = Subscription::with(['member', 'plan'])
            ->expiringSoon($this->subDays)
            ->when($this->search, fn ($q) => $q->whereHas('member', function ($q2) {
                $q2->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->orderBy('expires_at')
            ->get();

        return view('livewire.backoffice.members.expiry-dashboard', [
            'expiringCerts' => $certQuery,
            'expiringSubs' => $subQuery,
        ])->layout('layouts.backoffice');
    }
}
