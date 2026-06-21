<?php

namespace App\Livewire\Backoffice\Mesocycles;

use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
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

    /** @var array{setsAddedByMuscle: array<string,int>, feedbackTriggers: array<string>, action: string, note: string|null}|null */
    public ?array $lastProgressionResultData = null;

    public function mount(int $mesocycle): void
    {
        $this->mesocycleId = $mesocycle;
        $meso = Mesocycle::with([
            'weeks' => fn ($q) => $q->withCount(['sessions' => fn ($q2) => $q2->where('status', 'completed')]),
        ])->findOrFail($mesocycle);

        // Seleziona la settimana con più sessioni completed, altrimenti la prima
        $bestWeek = $meso->weeks
            ->sortByDesc('sessions_count')
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

    }

    public function applyProgression(): void
    {
        $service = app(WeeklyProgressionService::class);
        $result = $service->progressWeek($this->mesocycleId, $this->selectedWeekNumber);
        $this->lastProgressionResultData = [
            'setsAddedByMuscle' => $result->setsAddedByMuscle,
            'feedbackTriggers' => $result->feedbackTriggers,
            'action' => $result->action,
            'note' => $result->note,
        ];

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

        $deloadSignal = app(DeloadEvaluator::class)->evaluate($this->mesocycleId);
        $lastProgressionResult = $this->lastProgressionResultData !== null
            ? new ProgressionResult(
                setsAddedByMuscle: $this->lastProgressionResultData['setsAddedByMuscle'],
                feedbackTriggers: $this->lastProgressionResultData['feedbackTriggers'],
                action: $this->lastProgressionResultData['action'],
                note: $this->lastProgressionResultData['note'],
            )
            : null;

        return view('livewire.backoffice.mesocycles.mesocycle-detail', [
            'mesocycle' => $mesocycle,
            'deloadSignal' => $deloadSignal,
            'lastProgressionResult' => $lastProgressionResult,
        ])->layout('layouts.backoffice', ['page_title' => 'Mesociclo: '.$mesocycle->name]);
    }
}
