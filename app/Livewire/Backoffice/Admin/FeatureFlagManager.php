<?php

namespace App\Livewire\Backoffice\Admin;

use Illuminate\View\View;
use Laravel\Pennant\Feature;
use Livewire\Component;

class FeatureFlagManager extends Component
{
    /** @var list<string> */
    public array $flags = [
        'periodization_engine',
        'push_notifications',
        'group_classes',
        'financial_reports',
    ];

    public bool $confirmActive = false;

    public string $pendingFlag = '';

    public bool $pendingState = false;

    public function requestToggle(string $flag, bool $activate): void
    {
        $this->pendingFlag = $flag;
        $this->pendingState = $activate;
        $this->confirmActive = true;
    }

    public function confirmToggle(): void
    {
        if ($this->pendingState) {
            Feature::activateForEveryone($this->pendingFlag);
        } else {
            Feature::deactivateForEveryone($this->pendingFlag);
        }

        $this->reset(['confirmActive', 'pendingFlag', 'pendingState']);

        session()->flash('success', 'Feature flag aggiornato.');
    }

    public function cancelToggle(): void
    {
        $this->reset(['confirmActive', 'pendingFlag', 'pendingState']);
    }

    public function render(): View
    {
        $statuses = [];
        foreach ($this->flags as $flag) {
            $statuses[$flag] = Feature::active($flag);
        }

        return view('livewire.backoffice.admin.feature-flag-manager', [
            'statuses' => $statuses,
        ])->layout('layouts.backoffice', ['page_title' => 'Feature flags']);
    }
}
