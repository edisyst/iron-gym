<?php

namespace App\Livewire\Athlete;

use App\Models\ClassBooking;
use App\Models\Member;
use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Models\TrainingSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Laravel\Pennant\Feature;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Il mio allenamento')]
class Dashboard extends Component
{
    /** 'danger' = scaduto/mancante, 'warning' = in scadenza ≤30gg, '' = valido */
    public string $certWarningLevel = '';

    public ?Mesocycle $activeMesocycle = null;

    public ?MicrocycleWeek $currentWeek = null;

    /** @var Collection<int, TrainingSession> */
    public Collection $weekSessions;

    public ?TrainingSession $nextSession = null;

    public ?TrainingSession $lastSession = null;

    public float $lastTonnage = 0;

    public int $lastSetsCompleted = 0;

    /** @var Collection<int, ClassBooking> */
    public Collection $upcomingClassBookings;

    public function mount(): void
    {
        $this->weekSessions = collect();
        $this->upcomingClassBookings = collect();

        // Avviso certificato medico
        $member = Member::where('user_id', auth()->id())->first();
        if ($member) {
            $expiry = $member->medical_cert_expiry;
            if ($expiry === null || $expiry->isPast()) {
                $this->certWarningLevel = 'danger';
            } elseif ($expiry->lte(now()->addDays(30))) {
                $this->certWarningLevel = 'warning';
            }

            if (Feature::active('group_classes')) {
                $this->upcomingClassBookings = ClassBooking::with('occurrence.groupClass')
                    ->where('member_id', $member->id)
                    ->where('status', 'confirmed')
                    ->whereHas('occurrence', fn ($q) => $q
                        ->whereDate('date', '>=', today())
                        ->where('status', 'planned'))
                    ->join('class_occurrences', 'class_bookings.class_occurrence_id', '=', 'class_occurrences.id')
                    ->orderBy('class_occurrences.date')
                    ->orderBy('class_occurrences.start_time')
                    ->select('class_bookings.*')
                    ->limit(3)
                    ->get();
            }
        }

        // Cerca il mesociclo attivo dell'atleta con le settimane e sessioni
        $this->activeMesocycle = Mesocycle::where('athlete_id', auth()->id())
            ->where('status', 'active')
            ->with([
                'weeks' => fn ($q) => $q->orderBy('week_number'),
                'weeks.sessions' => fn ($q) => $q->orderBy('order_in_week'),
            ])
            ->latest()
            ->first();

        if ($this->activeMesocycle === null) {
            return;
        }

        $today = Carbon::today();

        // Trova la settimana corrente in base alle date
        foreach ($this->activeMesocycle->weeks as $week) {
            if ($today->between($week->start_date, $week->end_date)) {
                $this->currentWeek = $week;
                break;
            }
        }

        // Se non siamo nel range di date del mesociclo, prendi la prima settimana
        // con sessioni non ancora completate
        if ($this->currentWeek === null) {
            foreach ($this->activeMesocycle->weeks as $week) {
                $hasIncomplete = $week->sessions->contains(
                    fn (TrainingSession $s) => $s->status !== 'completed'
                );
                if ($hasIncomplete) {
                    $this->currentWeek = $week;
                    break;
                }
            }

            // Fallback: prima settimana
            if ($this->currentWeek === null) {
                $this->currentWeek = $this->activeMesocycle->weeks->first();
            }
        }

        if ($this->currentWeek !== null) {
            $this->weekSessions = $this->currentWeek->sessions->sortBy('order_in_week')->values();
        }

        // Prossima sessione planned o in_progress del mesociclo attivo
        $this->nextSession = TrainingSession::whereHas(
            'week.mesocycle',
            fn ($q) => $q->where('id', $this->activeMesocycle->id)
        )->whereIn('status', ['planned', 'in_progress'])
            ->with([
                'sessionExercises' => fn ($q) => $q->orderBy('order_in_session')->limit(5),
                'sessionExercises.exercise',
                'week',
            ])
            ->orderBy('scheduled_date')
            ->first();

        // Ultima sessione completata (qualunque mesociclo)
        $this->lastSession = TrainingSession::whereHas(
            'week.mesocycle',
            fn ($q) => $q->where('athlete_id', auth()->id())
        )->where('status', 'completed')
            ->orderByDesc('completed_at')
            ->first();

        if ($this->lastSession !== null) {
            $sessionId = $this->lastSession->id;

            $this->lastTonnage = (float) DB::table('exercise_sets as es')
                ->join('session_exercises as se', 'se.id', '=', 'es.session_exercise_id')
                ->where('se.session_id', $sessionId)
                ->where('es.is_warmup', false)
                ->whereNotNull('es.completed_at')
                ->whereNotNull('es.actual_weight_kg')
                ->whereNotNull('es.actual_reps')
                ->sum(DB::raw('es.actual_weight_kg * es.actual_reps'));

            $this->lastSetsCompleted = DB::table('exercise_sets as es')
                ->join('session_exercises as se', 'se.id', '=', 'es.session_exercise_id')
                ->where('se.session_id', $sessionId)
                ->where('es.is_warmup', false)
                ->whereNotNull('es.completed_at')
                ->count();
        }
    }

    /**
     * Label italiana per l'obiettivo
     */
    public function goalLabel(string $goal): string
    {
        return match ($goal) {
            'hypertrophy' => 'Ipertrofia',
            'strength' => 'Forza',
            'cut' => 'Definizione',
            'recomp' => 'Ricomposizione',
            'peaking' => 'Peaking',
            'general' => 'Generale',
            default => $goal,
        };
    }

    public function restoreSession(int $sessionId): void
    {
        TrainingSession::whereHas(
            'week.mesocycle', fn ($q) => $q->where('athlete_id', auth()->id())
        )->where('status', 'skipped')->findOrFail($sessionId)->update(['status' => 'planned']);

        $this->mount();
    }

    public function render(): View
    {
        return view('livewire.athlete.dashboard')
            ->layout('layouts.athlete');
    }
}
