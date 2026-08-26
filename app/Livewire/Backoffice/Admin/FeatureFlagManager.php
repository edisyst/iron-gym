<?php

namespace App\Livewire\Backoffice\Admin;

use App\Models\Setting;
use Illuminate\View\View;
use Laravel\Pennant\Feature;
use Livewire\Component;

class FeatureFlagManager extends Component
{
    /**
     * Flag validi per l'intera palestra: mappa flag => chiave in settings.
     * Gli altri flag restano risolti per utente dal rispettivo definer.
     */
    private const GLOBAL_FLAGS = [
        'group_classes' => 'group_classes_enabled',
    ];

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
        abort_unless(auth()->user()?->hasRole('gestore'), 403);
        abort_unless(in_array($this->pendingFlag, $this->flags, true), 403);

        if (isset(self::GLOBAL_FLAGS[$this->pendingFlag])) {
            // Flag globale: scrive in settings e azzera i valori memorizzati da
            // Pennant, altrimenti le righe per-utente gia' risolte vincerebbero
            // sul definer e il toggle non avrebbe effetto.
            Setting::write(self::GLOBAL_FLAGS[$this->pendingFlag], $this->pendingState);
            Feature::purge($this->pendingFlag);
        } elseif ($this->pendingState) {
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
