<?php

namespace App\Livewire\Backoffice\Athletes;

use App\Models\BodyMeasurement;
use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\ProgressPhoto;
use App\Models\User;
use App\Services\WeeklyVolumeCalculator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class AthleteAnalytics extends Component
{
    public int $athleteId;

    /** @var array{labels: list<string>, data: list<float|null>} */
    public array $weightChartData = ['labels' => [], 'data' => []];

    /** @var list<array{exercise_name: string, max_e1rm: float|null, sessions_count: int}> */
    public array $e1rmRows = [];

    /** @var list<array{muscle: string, hard_sets: float, status: string}> */
    public array $volumeRows = [];

    /** @var Collection<int, string> */
    public Collection $photoDates;

    public ?string $photoDate1 = null;

    public ?string $photoDate2 = null;

    /** @var array<string, ProgressPhoto> */
    public array $photos1 = [];

    /** @var array<string, ProgressPhoto> */
    public array $photos2 = [];

    public function mount(int $athleteId): void
    {
        User::findOrFail($athleteId);

        if (! auth()->user()?->hasRole('gestore')) {
            abort_unless(
                Mesocycle::where('athlete_id', $athleteId)
                    ->where('trainer_id', auth()->id())
                    ->exists(),
                403
            );
        }

        $this->athleteId = $athleteId;
        $this->photoDates = collect();

        $this->loadWeightData();
        $this->loadE1rmTable();
        $this->loadVolumeData();
        $this->loadPhotoDates();
    }

    private function loadWeightData(): void
    {
        $measurements = BodyMeasurement::where('athlete_id', $this->athleteId)
            ->where('measured_at', '>=', now()->subDays(90)->toDateString())
            ->whereNotNull('weight_kg')
            ->orderBy('measured_at')
            ->get(['measured_at', 'weight_kg']);

        $labels = [];
        $data = [];
        foreach ($measurements as $m) {
            $labels[] = Carbon::parse($m->measured_at)->format('d/m');
            $data[] = (float) $m->weight_kg;
        }

        $this->weightChartData = ['labels' => $labels, 'data' => $data];
    }

    private function loadE1rmTable(): void
    {
        // Top 5 esercizi per sessioni negli ultimi 30 giorni con max e1RM Epley (Epley in SQL)
        $rows = DB::table('exercise_sets as es')
            ->join('session_exercises as se', 'se.id', '=', 'es.session_exercise_id')
            ->join('training_sessions as s', 's.id', '=', 'se.session_id')
            ->join('microcycle_weeks as mw', 'mw.id', '=', 's.microcycle_week_id')
            ->join('mesocycles as m', 'm.id', '=', 'mw.mesocycle_id')
            ->join('exercises as e', 'e.id', '=', 'se.exercise_id')
            ->where('m.athlete_id', $this->athleteId)
            ->where('s.status', 'completed')
            ->where('es.is_warmup', false)
            ->whereNotNull('es.completed_at')
            ->whereNotNull('es.actual_weight_kg')
            ->whereNotNull('es.actual_reps')
            ->where('es.actual_reps', '>', 0)
            ->where('es.completed_at', '>=', now()->subDays(30))
            ->select(
                'se.exercise_id',
                'e.name_it',
                DB::raw('MAX(es.actual_weight_kg * (1 + es.actual_reps / 30.0)) as max_e1rm'),
                DB::raw('COUNT(DISTINCT s.id) as sessions_count')
            )
            ->groupBy('se.exercise_id', 'e.name_it')
            ->orderByDesc('sessions_count')
            ->limit(5)
            ->get();

        $this->e1rmRows = $rows->map(fn ($row) => [
            'exercise_name' => $row->name_it,
            'max_e1rm' => $row->max_e1rm !== null ? round((float) $row->max_e1rm, 1) : null,
            'sessions_count' => (int) $row->sessions_count,
        ])->all();
    }

    private function loadVolumeData(): void
    {
        $this->volumeRows = [];

        // Settimana corrente del mesociclo attivo dell'atleta (se esiste)
        $currentWeek = MicrocycleWeek::whereHas('mesocycle', fn ($q) => $q
            ->where('athlete_id', $this->athleteId)
            ->where('status', 'active')
        )
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->first();

        if ($currentWeek === null) {
            return;
        }

        $calculator = app(WeeklyVolumeCalculator::class);
        $volume = $calculator->calculate($this->athleteId, $currentWeek->id);

        foreach ($volume as $muscleName => $data) {
            $this->volumeRows[] = [
                'muscle' => $muscleName,
                'hard_sets' => $data['hard_sets'],
                'status' => $data['status'],
            ];
        }
    }

    private function loadPhotoDates(): void
    {
        // Recupera le date distinte come stringhe direttamente dalla query
        $this->photoDates = ProgressPhoto::where('athlete_id', $this->athleteId)
            ->select('taken_at')
            ->distinct()
            ->orderByDesc('taken_at')
            ->pluck('taken_at')
            ->map(fn ($d) => $d instanceof Carbon ? $d->toDateString() : (string) $d);
    }

    public function updatedPhotoDate1(): void
    {
        $this->photos1 = $this->photoDate1
            ? ProgressPhoto::where('athlete_id', $this->athleteId)
                ->where('taken_at', $this->photoDate1)
                ->get()
                ->keyBy('pose')
                ->toArray()
            : [];
    }

    public function updatedPhotoDate2(): void
    {
        $this->photos2 = $this->photoDate2
            ? ProgressPhoto::where('athlete_id', $this->athleteId)
                ->where('taken_at', $this->photoDate2)
                ->get()
                ->keyBy('pose')
                ->toArray()
            : [];
    }

    public function render(): View
    {
        return view('livewire.backoffice.athletes.athlete-analytics')
            ->with('athlete', User::findOrFail($this->athleteId))
            ->layout('layouts.backoffice', ['page_title' => 'Analytics atleta']);
    }
}
