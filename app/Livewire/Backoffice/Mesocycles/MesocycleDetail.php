<?php

namespace App\Livewire\Backoffice\Mesocycles;

use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\TrainingSession;
use App\Services\DeloadEvaluator;
use App\Services\WeeklyProgressionService;
use App\Services\WeeklyVolumeCalculator;
use App\ValueObjects\DeloadSignal;
use App\ValueObjects\ProgressionResult;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dettaglio mesociclo')]
class MesocycleDetail extends Component
{
    public int $mesocycleId;

    public int $selectedWeekNumber = 1;

    /** @var array<string, array{hard_sets: float, mev: int|null, mav_min: int|null, mav_max: int|null, mrv: int|null, status: string}> */
    public array $volumeData = [];

    public ?ProgressionResult $lastProgressionResult = null;

    public ?DeloadSignal $deloadSignal = null;

    public function mount(int $mesocycle): void
    {
        $this->mesocycleId = $mesocycle;
        $meso = Mesocycle::with('weeks')->findOrFail($mesocycle);

        // Seleziona la settimana con più sessioni completed, altrimenti la prima
        $bestWeek = $meso->weeks
            ->sortByDesc(fn ($w) => TrainingSession::where('microcycle_week_id', $w->id)->where('status', 'completed')->count())
            ->first();

        $this->selectedWeekNumber = $bestWeek !== null ? $bestWeek->week_number : 1;

        $this->loadVolume();
    }

    public function loadVolume(): void
    {
        $meso = Mesocycle::findOrFail($this->mesocycleId);
        $week = MicrocycleWeek::where('mesocycle_id', $meso->id)
            ->where('week_number', $this->selectedWeekNumber)
            ->first();

        if ($week === null) {
            $this->volumeData = [];

            return;
        }

        $calc = app(WeeklyVolumeCalculator::class);
        $this->volumeData = $calc->calculate($meso->athlete_id, $week->id);

        $evaluator = app(DeloadEvaluator::class);
        $this->deloadSignal = $evaluator->evaluate($this->mesocycleId);
    }

    public function applyProgression(): void
    {
        $service = app(WeeklyProgressionService::class);
        $this->lastProgressionResult = $service->progressWeek($this->mesocycleId, $this->selectedWeekNumber);

        session()->flash('success', 'Progressione applicata per la settimana '.($this->selectedWeekNumber + 1).'.');

        $this->loadVolume();
    }

    public function forceDeload(): void
    {
        $meso = Mesocycle::with('weeks')->findOrFail($this->mesocycleId);

        $nextWeek = $meso->weeks->firstWhere('week_number', $this->selectedWeekNumber + 1);
        if ($nextWeek === null) {
            session()->flash('error', 'Nessuna settimana successiva da marcare come deload.');

            return;
        }

        $nextWeek->update(['is_deload' => true]);

        $service = app(WeeklyProgressionService::class);
        $this->lastProgressionResult = $service->progressWeek($this->mesocycleId, $this->selectedWeekNumber);

        session()->flash('success', 'Deload forzato applicato alla settimana '.$nextWeek->week_number.'.');

        $this->loadVolume();
    }

    public function render(): View
    {
        $mesocycle = Mesocycle::with(['weeks', 'athlete', 'trainer'])->findOrFail($this->mesocycleId);

        return view('livewire.backoffice.mesocycles.mesocycle-detail', [
            'mesocycle' => $mesocycle,
        ])->layout('layouts.backoffice', ['page_title' => 'Mesociclo: '.$mesocycle->name]);
    }
}
