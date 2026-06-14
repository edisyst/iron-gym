<?php

namespace App\Livewire\Backoffice\Athletes;

use App\Models\BodyMeasurement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

class BodyMeasurementForm extends Component
{
    public int $athleteId;

    public string $measuredAt = '';

    public ?float $weightKg = null;

    public ?float $bodyFatPct = null;

    public ?float $chestCm = null;

    public ?float $waistCm = null;

    public ?float $hipsCm = null;

    public ?float $leftArmCm = null;

    public ?float $rightArmCm = null;

    public ?float $leftThighCm = null;

    public ?float $rightThighCm = null;

    public ?float $leftCalfCm = null;

    public ?float $rightCalfCm = null;

    public ?string $notes = null;

    /** @var Collection<int, BodyMeasurement> */
    public Collection $recentMeasurements;

    public function mount(int $athleteId): void
    {
        // Verifica che l'atleta esista
        User::findOrFail($athleteId);
        $this->athleteId = $athleteId;
        $this->measuredAt = now()->toDateString();
        $this->recentMeasurements = collect();
        $this->loadRecentMeasurements();
    }

    private function loadRecentMeasurements(): void
    {
        $this->recentMeasurements = BodyMeasurement::where('athlete_id', $this->athleteId)
            ->orderByDesc('measured_at')
            ->limit(5)
            ->get();
    }

    public function save(): void
    {
        $this->validate([
            'measuredAt' => 'required|date',
            'weightKg' => 'nullable|numeric|min:0|max:500',
            'bodyFatPct' => 'nullable|numeric|min:0|max:100',
            'chestCm' => 'nullable|numeric|min:0|max:300',
            'waistCm' => 'nullable|numeric|min:0|max:300',
            'hipsCm' => 'nullable|numeric|min:0|max:300',
            'leftArmCm' => 'nullable|numeric|min:0|max:300',
            'rightArmCm' => 'nullable|numeric|min:0|max:300',
            'leftThighCm' => 'nullable|numeric|min:0|max:300',
            'rightThighCm' => 'nullable|numeric|min:0|max:300',
            'leftCalfCm' => 'nullable|numeric|min:0|max:300',
            'rightCalfCm' => 'nullable|numeric|min:0|max:300',
        ]);

        BodyMeasurement::create([
            'athlete_id' => $this->athleteId,
            'measured_at' => $this->measuredAt,
            'weight_kg' => $this->weightKg,
            'body_fat_pct' => $this->bodyFatPct,
            'chest_cm' => $this->chestCm,
            'waist_cm' => $this->waistCm,
            'hips_cm' => $this->hipsCm,
            'left_arm_cm' => $this->leftArmCm,
            'right_arm_cm' => $this->rightArmCm,
            'left_thigh_cm' => $this->leftThighCm,
            'right_thigh_cm' => $this->rightThighCm,
            'left_calf_cm' => $this->leftCalfCm,
            'right_calf_cm' => $this->rightCalfCm,
            'notes' => $this->notes,
            'recorded_by' => auth()->id(),
        ]);

        $this->loadRecentMeasurements();
        $this->dispatch('saved');
        session()->flash('success', 'Misurazione salvata.');

        // Reset campi numerici dopo salvataggio
        $this->reset([
            'weightKg', 'bodyFatPct', 'chestCm', 'waistCm', 'hipsCm',
            'leftArmCm', 'rightArmCm', 'leftThighCm', 'rightThighCm',
            'leftCalfCm', 'rightCalfCm', 'notes',
        ]);
        $this->measuredAt = now()->toDateString();
    }

    public function render(): View
    {
        return view('livewire.backoffice.athletes.body-measurement-form')
            ->with('athlete', User::findOrFail($this->athleteId));
    }
}
