<?php

namespace App\Livewire\Backoffice\Subscriptions;

use App\Models\Member;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Component;

class SubscriptionForm extends Component
{
    public string $member_id = '';

    public string $plan_id = '';

    public string $started_at = '';

    public string $expires_at = '';

    public ?int $accesses_remaining = null;

    public string $notes = '';

    public function mount(): void
    {
        $this->started_at = today()->format('Y-m-d');
    }

    public function updatedPlanId(): void
    {
        if ($this->plan_id && $this->started_at) {
            $plan = SubscriptionPlan::find($this->plan_id);
            if ($plan) {
                $this->expires_at = Carbon::parse($this->started_at)
                    ->addDays($plan->duration_days)
                    ->format('Y-m-d');
                $this->accesses_remaining = $plan->max_accesses;
            }
        }
    }

    public function updatedStartedAt(): void
    {
        $this->updatedPlanId();
    }

    /** @return array<string, string> */
    protected function rules(): array
    {
        return [
            'member_id' => 'required|exists:members,id',
            'plan_id' => 'required|exists:subscription_plans,id',
            'started_at' => 'required|date',
            'notes' => 'nullable|string',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $plan = SubscriptionPlan::findOrFail($this->plan_id);
        $expiresAt = Carbon::parse($this->started_at)->addDays($plan->duration_days);

        Subscription::create([
            'member_id' => $this->member_id,
            'plan_id' => $this->plan_id,
            'started_at' => $this->started_at,
            'expires_at' => $expiresAt->toDateString(),
            'accesses_remaining' => $plan->max_accesses,
            'created_by' => auth()->id(),
            'notes' => $this->notes ?: null,
        ]);

        session()->flash('success', 'Abbonamento creato con successo.');
        $this->redirect(route('backoffice.subscriptions.index'));
    }

    public function render(): View
    {
        return view('livewire.backoffice.subscriptions.subscription-form', [
            'members' => Member::where('is_active', true)->orderBy('last_name')->orderBy('first_name')->get(),
            'plans' => SubscriptionPlan::where('is_active', true)->orderBy('name')->get(),
        ])->layout('layouts.backoffice')
            ->layoutData(['page_title' => 'Nuovo abbonamento']);
    }
}
