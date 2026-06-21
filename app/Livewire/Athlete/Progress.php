<?php

namespace App\Livewire\Athlete;

use App\Models\BodyMeasurement;
use App\Models\MicrocycleWeek;
use App\Services\E1rmCalculator;
use App\Services\WeeklyVolumeCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Progressi')]
class Progress extends Component
{
    public string $activeTab = 'body';

    public ?int $selectedExerciseId = null;

    /** @var array{labels: list<string>, data: list<float|null>} */
    public array $weightChartData = ['labels' => [], 'data' => []];

    /** @var array{labels: list<string>, data: list<float|null>, pr: float|null} */
    public array $e1rmChartData = ['labels' => [], 'data' => [], 'pr' => null];

    /** @var array{labels: list<string>, datasets: list<array{label: string, data: list<float>}>} */
    public array $volumeChartData = ['labels' => [], 'datasets' => []];

    /** @var list<array{id: int, name_it: string}> */
    public array $exercises = [];

    public function mount(): void
    {
        $this->loadExercises();
        $this->loadWeightData();
        $this->loadVolumeData();
    }

    private function loadExercises(): void
    {
        // Esercizi distinti loggati dall'atleta (con almeno un set completed)
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
    }

    public function loadE1rmData(int $exerciseId): void
    {
        $this->selectedExerciseId = $exerciseId;
        $athleteId = auth()->id();

        // Set completati (non warmup) con dati sufficienti per Epley
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
                's.id as session_id',
                DB::raw('DATE(es.completed_at) as session_date'),
                'es.actual_weight_kg',
                'es.actual_reps'
            )
            ->get();

        // Calcola e1RM massimo per sessione (group by session_date)
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
        $calculator = app(WeeklyVolumeCalculator::class);

        // Settimane degli ultimi 6 mesi con almeno una sessione completed
        $weeks = MicrocycleWeek::whereHas('sessions', fn ($q) => $q->where('status', 'completed'))
            ->whereHas('mesocycle', fn ($q) => $q->where('athlete_id', $athleteId))
            ->where('start_date', '>=', now()->subMonths(6)->toDateString())
            ->orderBy('start_date')
            ->get();

        // Gruppi muscolari da aggregare
        $groups = [
            'chest' => 'Petto',
            'back' => 'Schiena',
            'shoulders' => 'Spalle',
            'arms' => 'Braccia',
            'legs' => 'Gambe',
            'core' => 'Core',
        ];

        $labels = [];
        /** @var array<string, list<float>> $groupData */
        $groupData = array_fill_keys(array_keys($groups), []);

        foreach ($weeks as $week) {
            $labels[] = 'W'.$week->week_number.' '.$week->start_date->format('d/m');
            $volume = $calculator->calculate($athleteId, $week->id);

            foreach (array_keys($groups) as $group) {
                // Somma hard_sets di tutti i muscoli del gruppo
                $total = 0.0;
                foreach ($volume as $muscleName => $data) {
                    // Il calcolatore usa il name_it come chiave; usiamo muscle_group
                    // Il calcolatore restituisce keyed per nome muscolo — aggreghiamo per gruppo
                    // accedendo tramite JOIN: recuperiamo muscle_group dal DB
                    $total += 0.0; // placeholder; vedi sotto
                }
                $groupData[$group][] = $total;
            }
        }

        // Ricalcolo corretto: raggruppiamo per muscle_group tramite query separata
        $labels = [];
        $groupData = array_fill_keys(array_keys($groups), []);

        foreach ($weeks as $week) {
            $labels[] = 'W'.$week->week_number.' '.$week->start_date->format('d/m');

            // Hard sets per muscle_group in questa settimana
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

        $this->volumeChartData = [
            'labels' => $labels,
            'datasets' => $datasets,
        ];

        $this->dispatch('volumeDataLoaded');
    }

    public function render(): View
    {
        return view('livewire.athlete.progress')
            ->layout('layouts.athlete');
    }
}
