<?php

namespace App\Livewire\Backoffice\Reports;

use App\Models\Mesocycle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class TrainingReport extends Component
{
    public string $dateFrom = '';

    public string $dateTo = '';

    public string $mesoStatus = 'all';

    public ?int $drilldownAthleteId = null;

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->endOfMonth()->toDateString();
    }

    public function openDrilldown(int $athleteId): void
    {
        $user = auth()->user();

        if (! $user->hasRole('gestore')) {
            abort_unless(
                Mesocycle::where('athlete_id', $athleteId)
                    ->where('trainer_id', $user->id)
                    ->exists(),
                403
            );
        }

        $this->drilldownAthleteId = $athleteId;
    }

    public function closeDrilldown(): void
    {
        $this->drilldownAthleteId = null;
    }

    public function render(): View
    {
        $from = Carbon::parse($this->dateFrom)->startOfDay();
        $to = Carbon::parse($this->dateTo)->endOfDay();
        $user = auth()->user();

        // Trainer loggato vede solo i suoi atleti; gestore vede tutti
        $trainerFilter = $user->hasRole('gestore') ? null : $user->id;

        $athleteRows = $this->loadAthleteRows($from, $to, $trainerFilter);
        $drilldown = null;
        if ($this->drilldownAthleteId !== null) {
            $drilldown = $this->loadDrilldown($this->drilldownAthleteId);
        }

        return view('livewire.backoffice.reports.training-report', compact('athleteRows', 'drilldown'))
            ->layout('layouts.backoffice')
            ->layoutData(['page_title' => 'Report allenamento']);
    }

    /**
     * @return Collection<int, \stdClass>
     */
    private function loadAthleteRows(Carbon $from, Carbon $to, ?int $trainerId)
    {
        $query = DB::table('members as m')
            ->join('users as u', 'u.id', '=', 'm.user_id')
            ->leftJoin('mesocycles as mc', function ($j) {
                $j->on('mc.athlete_id', '=', 'u.id')
                    ->where('mc.status', 'active');
            })
            ->leftJoin('microcycle_weeks as mw', function ($j) {
                $j->on('mw.mesocycle_id', '=', 'mc.id');
            })
            ->leftJoin('training_sessions as ts_all', function ($j) {
                $j->on('ts_all.microcycle_week_id', '=', 'mw.id');
            })
            ->leftJoin('training_sessions as ts_done', function ($j) use ($from, $to) {
                $j->on('ts_done.microcycle_week_id', '=', 'mw.id')
                    ->where('ts_done.status', 'completed')
                    ->whereBetween('ts_done.completed_at', [$from->toDateTimeString(), $to->toDateTimeString()]);
            })
            ->leftJoin('training_sessions as ts_skip', function ($j) use ($from, $to) {
                $j->on('ts_skip.microcycle_week_id', '=', 'mw.id')
                    ->where('ts_skip.status', 'skipped')
                    ->whereBetween('ts_skip.scheduled_date', [$from->toDateString(), $to->toDateString()]);
            })
            ->select(
                'u.id as athlete_id',
                DB::raw("CONCAT(m.first_name, ' ', m.last_name) as nome"),
                'mc.name as mesociclo',
                DB::raw('COUNT(DISTINCT ts_done.id) as sessioni_completate'),
                DB::raw('COUNT(DISTINCT ts_skip.id) as sessioni_saltate'),
                DB::raw('COUNT(DISTINCT ts_all.id) as sessioni_pianificate'),
            )
            ->groupBy('u.id', 'm.first_name', 'm.last_name', 'mc.name', 'mc.id');

        if ($trainerId !== null) {
            $query->where('mc.trainer_id', $trainerId);
        }

        if ($this->mesoStatus !== 'all') {
            $query->where('mc.status', $this->mesoStatus);
        }

        return $query->get()->map(function ($row) {
            $planned = (int) $row->sessioni_pianificate;
            $done = (int) $row->sessioni_completate;
            $row->adherence_rate = $planned > 0 ? round(($done / $planned) * 100, 1) : 0.0;

            return $row;
        });
    }

    /**
     * @return array{athlete_name: string, weekly_sessions: array<string, int>, feedbacks: list<object>}
     */
    private function loadDrilldown(int $athleteId): array
    {
        $athleteName = DB::table('users')->where('id', $athleteId)->value('name') ?? '';

        // Sessioni per settimana negli ultimi 8 mesocicli
        $weeklySessions = DB::table('training_sessions as ts')
            ->join('microcycle_weeks as mw', 'mw.id', '=', 'ts.microcycle_week_id')
            ->join('mesocycles as mc', 'mc.id', '=', 'mw.mesocycle_id')
            ->where('mc.athlete_id', $athleteId)
            ->where('ts.status', 'completed')
            ->whereIn('mc.id', function ($sub) use ($athleteId) {
                $sub->select('id')
                    ->from('mesocycles')
                    ->where('athlete_id', $athleteId)
                    ->orderByDesc('start_date')
                    ->limit(8);
            })
            ->select(
                DB::raw("CONCAT(mc.name, ' — Sett. ', mw.week_number) as label"),
                DB::raw('COUNT(ts.id) as session_count'),
            )
            ->groupBy('mw.id', 'mc.name', 'mw.week_number')
            ->orderBy('mw.start_date')
            ->pluck('session_count', 'label')
            ->all();

        // Ultimi 5 feedback
        $feedbacks = DB::table('session_feedbacks as sf')
            ->join('training_sessions as ts', 'ts.id', '=', 'sf.session_id')
            ->join('microcycle_weeks as mw', 'mw.id', '=', 'ts.microcycle_week_id')
            ->join('mesocycles as mc', 'mc.id', '=', 'mw.mesocycle_id')
            ->where('mc.athlete_id', $athleteId)
            ->select(
                'ts.scheduled_date',
                'sf.pump',
                'sf.soreness_prev',
                'sf.perceived_effort',
                'sf.joint_pain',
                'sf.performance',
                'sf.note',
            )
            ->orderByDesc('ts.scheduled_date')
            ->limit(5)
            ->get()
            ->all();

        return [
            'athlete_name' => $athleteName,
            'weekly_sessions' => $weeklySessions,
            'feedbacks' => $feedbacks,
        ];
    }
}
