<?php

namespace App\Livewire\Athlete;

use App\Models\BodyMeasurement;
use App\Models\Mesocycle;
use App\Models\SessionExercise;
use App\Models\TrainingSession;
use App\Services\E1rmCalculator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Allenamenti')]
class TrainingHub extends Component
{
    use WithPagination;

    // Tab esterno: storico | progressi
    public string $mainTab = 'storico';

    // --- Storico ---
    public string $mesocycleId = '';

    public ?int $selectedSessionId = null;

    public ?int $exerciseHistoryId = null;

    public string $exerciseHistoryName = '';

    // --- Progressi ---
    public string $progressTab = 'body';

    public ?int $selectedExerciseId = null;

    public bool $progressDataLoaded = false;

    /** @var array{labels: list<string>, data: list<float|null>} */
    public array $weightChartData = ['labels' => [], 'data' => []];

    /** @var array{labels: list<string>, data: list<float|null>, pr: float|null} */
    public array $e1rmChartData = ['labels' => [], 'data' => [], 'pr' => null];

    /** @var array{labels: list<string>, datasets: list<array{label: string, data: list<float>}>} */
    public array $volumeChartData = ['labels' => [], 'datasets' => []];

    /** @var list<array{id: int, name_it: string}> */
    public array $exercises = [];

    // --- Storico: metodi ---

    public function updatingMesocycleId(): void
    {
        $this->resetPage();
        $this->selectedSessionId = null;
    }

    public function showExerciseHistory(int $exerciseId, string $name): void
    {
        if ($this->exerciseHistoryId === $exerciseId) {
            $this->exerciseHistoryId = null;
            $this->exerciseHistoryName = '';

            return;
        }

        $this->exerciseHistoryId = $exerciseId;
        $this->exerciseHistoryName = $name;
    }

    public function showDetail(int $sessionId): void
    {
        $this->selectedSessionId = $this->selectedSessionId === $sessionId ? null : $sessionId;
    }

    /** @return Collection<int, SessionExercise> */
    public function getExerciseHistoryProperty(): Collection
    {
        if ($this->exerciseHistoryId === null) {
            return collect();
        }

        return SessionExercise::where('exercise_id', $this->exerciseHistoryId)
            ->whereHas('session', fn ($q) => $q
                ->where('status', 'completed')
                ->whereHas('week.mesocycle', fn ($q2) => $q2->where('athlete_id', auth()->id())))
            ->with([
                'session',
                'sets' => fn ($q) => $q->orderBy('set_index'),
            ])
            ->join('training_sessions', 'training_sessions.id', '=', 'session_exercises.session_id')
            ->orderByDesc('training_sessions.completed_at')
            ->select('session_exercises.*')
            ->get();
    }

    public function getSelectedSessionProperty(): ?TrainingSession
    {
        if ($this->selectedSessionId === null) {
            return null;
        }

        return TrainingSession::whereHas(
            'week.mesocycle',
            fn ($q) => $q->where('athlete_id', auth()->id())
        )
            ->with([
                'sessionExercises' => fn ($q) => $q->orderBy('order_in_session'),
                'sessionExercises.exercise',
                'sessionExercises.sets' => fn ($q) => $q->orderBy('set_index'),
            ])
            ->find($this->selectedSessionId);
    }

    public function duration(TrainingSession $session): ?string
    {
        if ($session->started_at === null || $session->completed_at === null) {
            return null;
        }

        return ((int) $session->started_at->diffInMinutes($session->completed_at)).' min';
    }

    public function completedSetsCount(TrainingSession $session): int
    {
        return $session->sessionExercises->sum(
            fn ($e) => $e->sets->whereNotNull('completed_at')->count()
        );
    }

    // --- Progressi: metodi ---

    public function switchToProgress(): void
    {
        $this->mainTab = 'progress';

        if (! $this->progressDataLoaded) {
            $this->loadProgressData();
        }
    }

    public function loadProgressData(): void
    {
        $this->loadExercises();
        $this->loadWeightData();
        $this->loadVolumeData();
        $this->progressDataLoaded = true;
    }

    private function loadExercises(): void
    {
        $athleteId = auth()->id();
        $this->exercises = DB::table('exercise_sets as es')
            ->join('session_exercises as se', 'se.id', '=', 'es.session_exercise_id')
            ->join('training_sessions as s', 's.id', '=', 'se.session_id')
            ->join('microcycle_weeks as mw', 'mw.id', '=', 's.microcycle_week_id')
            ->join('mesocycles as m', 'm.id', '=', 'mw.mesocycle_id')
            ->join('exercises as e', 'e.id', '=', 'se.exercise_id')
            ->where('m.athlete_id', $athleteId)
            ->whereNotNull('es.completed_at')
            ->select('e.id', 'e.name_it')
            ->distinct()
            ->orderBy('e.name_it')
            ->get()
            ->map(fn ($r) => ['id' => $r->id, 'name_it' => $r->name_it])
            ->values()
            ->toArray();
    }

    public function loadWeightData(): void
    {
        $measurements = BodyMeasurement::where('athlete_id', auth()->id())
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
        $this->dispatch('weightDataLoaded');
    }

    public function loadE1rmData(int $exerciseId): void
    {
        $this->selectedExerciseId = $exerciseId;
        $athleteId = auth()->id();

        $rows = DB::table('exercise_sets as es')
            ->join('session_exercises as se', 'se.id', '=', 'es.session_exercise_id')
            ->join('training_sessions as s', 's.id', '=', 'se.session_id')
            ->join('microcycle_weeks as mw', 'mw.id', '=', 's.microcycle_week_id')
            ->join('mesocycles as m', 'm.id', '=', 'mw.mesocycle_id')
            ->where('m.athlete_id', $athleteId)
            ->where('se.exercise_id', $exerciseId)
            ->where('s.status', 'completed')
            ->where('es.is_warmup', false)
            ->whereNotNull('es.actual_weight_kg')
            ->whereNotNull('es.actual_reps')
            ->where('es.actual_reps', '>', 0)
            ->whereNotNull('es.completed_at')
            ->select(
                DB::raw('DATE(es.completed_at) as session_date'),
                'es.actual_weight_kg',
                'es.actual_reps'
            )
            ->get();

        $byDate = [];
        foreach ($rows as $row) {
            $e1rm = E1rmCalculator::epley((float) $row->actual_weight_kg, (int) $row->actual_reps);
            if ($e1rm === null) {
                continue;
            }
            $date = $row->session_date;
            if (! isset($byDate[$date]) || $e1rm > $byDate[$date]) {
                $byDate[$date] = $e1rm;
            }
        }

        ksort($byDate);
        $pr = $byDate ? max($byDate) : null;

        $this->e1rmChartData = [
            'labels' => array_map(fn ($d) => date('d/m', strtotime($d)), array_keys($byDate)),
            'data' => array_values($byDate),
            'pr' => $pr,
        ];

        $this->dispatch('e1rmDataLoaded');
    }

    public function loadVolumeData(): void
    {
        $athleteId = auth()->id();
        $since = now()->subMonths(6)->toDateString();

        // Raw query coerente con loadExercises/loadE1rmData: nessun soft-delete scope su mesocycles
        $weeks = DB::table('microcycle_weeks as mw')
            ->join('mesocycles as m', 'm.id', '=', 'mw.mesocycle_id')
            ->whereNull('m.deleted_at')
            ->where('m.athlete_id', $athleteId)
            ->where('mw.start_date', '>=', $since)
            ->whereExists(function ($q) {
                $q->from('training_sessions as s')
                    ->whereColumn('s.microcycle_week_id', 'mw.id')
                    ->where('s.status', 'completed');
            })
            ->orderBy('mw.start_date')
            ->select('mw.id', 'mw.week_number', 'mw.start_date')
            ->get();

        $groups = [
            'chest' => 'Petto',
            'back' => 'Schiena',
            'shoulders' => 'Spalle',
            'arms' => 'Braccia',
            'legs' => 'Gambe',
            'core' => 'Core',
        ];

        $labels = [];
        $groupData = array_fill_keys(array_keys($groups), []);

        foreach ($weeks as $week) {
            $labels[] = 'W'.$week->week_number.' '.date('d/m', strtotime($week->start_date));

            $weekGroupVolume = DB::table('exercise_sets as es')
                ->join('session_exercises as se', 'se.id', '=', 'es.session_exercise_id')
                ->join('training_sessions as s', 's.id', '=', 'se.session_id')
                ->join('exercise_muscle as em', 'em.exercise_id', '=', 'se.exercise_id')
                ->join('muscles as mu', 'mu.id', '=', 'em.muscle_id')
                ->where('s.microcycle_week_id', $week->id)
                ->where('s.status', 'completed')
                ->where('es.is_warmup', false)
                ->whereNotNull('es.completed_at')
                ->where('em.role', 'primary')
                ->select('mu.muscle_group', DB::raw('SUM(em.contribution_pct / 100.0) as hard_sets'))
                ->groupBy('mu.muscle_group')
                ->get()
                ->keyBy('muscle_group');

            foreach (array_keys($groups) as $group) {
                $row = $weekGroupVolume->get($group);
                $groupData[$group][] = $row !== null ? round((float) $row->hard_sets, 1) : 0.0;
            }
        }

        $datasets = [];
        foreach ($groups as $key => $label) {
            $datasets[] = ['label' => $label, 'data' => $groupData[$key]];
        }

        $this->volumeChartData = ['labels' => $labels, 'datasets' => $datasets];
        $this->dispatch('volumeDataLoaded', labels: $labels, datasets: $datasets);
    }

    public function render(): View
    {
        $mesocycles = Mesocycle::where('athlete_id', auth()->id())
            ->orderByDesc('start_date')
            ->get();

        $sessions = TrainingSession::whereHas(
            'week.mesocycle',
            fn ($q) => $q->where('athlete_id', auth()->id())
                ->when($this->mesocycleId !== '', fn ($q2) => $q2->where('id', $this->mesocycleId))
        )
            ->where('status', 'completed')
            ->with(['week.mesocycle', 'sessionExercises.sets'])
            ->orderByDesc('completed_at')
            ->paginate(20);

        return view('livewire.athlete.training-hub', [
            'mesocycles' => $mesocycles,
            'sessions' => $sessions,
        ])->layout('layouts.athlete');
    }
}
