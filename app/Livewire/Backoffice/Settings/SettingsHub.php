<?php

namespace App\Livewire\Backoffice\Settings;

use Illuminate\View\View;
use Livewire\Component;

class SettingsHub extends Component
{
    public function render(): View
    {
        return view('livewire.backoffice.settings.settings-hub')
            ->layout('layouts.backoffice', ['page_title' => 'Impostazioni']);
    }
}
