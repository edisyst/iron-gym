<?php

namespace App\Livewire\Backoffice\Settings;

use App\Models\Setting;
use Illuminate\View\View;
use Laravel\Pennant\Feature;
use Livewire\Component;

class FeatureFlagManager extends Component
{
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
        abort_unless(auth()->user()?->hasRole('gestore'), 403);
        abort_unless(array_key_exists($this->pendingFlag, config('features.managed_flags', [])), 403);

        $meta = config("features.managed_flags.{$this->pendingFlag}");
        Setting::write($meta['settings_key'], $this->pendingState);
        Feature::purge($this->pendingFlag);

        $this->reset(['confirmActive', 'pendingFlag', 'pendingState']);
        session()->flash('success', 'Feature flag aggiornato.');
    }

    public function cancelToggle(): void
    {
        $this->reset(['confirmActive', 'pendingFlag', 'pendingState']);
    }

    public function render(): View
    {
        $managedFlags = config('features.managed_flags', []);

        $statuses = [];
        foreach ($managedFlags as $flag => $meta) {
            $statuses[$flag] = Setting::bool($meta['settings_key'], $meta['default']);
        }

        return view('livewire.backoffice.settings.feature-flag-manager', [
            'managedFlags' => $managedFlags,
            'statuses' => $statuses,
        ])->layout('layouts.backoffice', ['page_title' => 'Funzioni']);
    }
}
