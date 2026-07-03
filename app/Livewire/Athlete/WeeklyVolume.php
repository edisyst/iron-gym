<?php

namespace App\Livewire\Athlete;

use App\Models\Mesocycle;
use App\Models\MicrocycleWeek;
use App\Services\WeeklyVolumeCalculator;
use Carbon\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Volume settimanale')]
class WeeklyVolume extends Component
{
    public ?int $selectedWeekId = null;

    /** @var array<int, array{id: int, week_number: int, is_deload: bool, label: string}> */
    public array $weeks = [];

    /**
     * Risultato di WeeklyVolumeCalculator::calculate().
     *
     * @var array<string, array{hard_sets: float, mev: int|null, mav_min: int|null, mav_max: int|null, mrv: int|null, status: string}>
     */
    public array $volumeData = [];

    /**
     * Mappa slug → classe CSS intensità (intensity-0 .. intensity-5).
     * Alimenta la colorazione dei path SVG senza round-trip.
     *
     * @var array<string, string>
     */
    public array $intensityMap = [];

    public function mount(): void
    {
        $mesocycle = Mesocycle::where('athlete_id', auth()->id())
            ->where('status', 'active')
            ->with(['weeks' => fn ($q) => $q->orderBy('week_number')])
            ->latest()
            ->first();

        if ($mesocycle === null) {
            return;
        }

        $this->weeks = $mesocycle->weeks->map(fn (MicrocycleWeek $w) => [
            'id' => $w->id,
            'week_number' => $w->week_number,
            'is_deload' => $w->is_deload,
            'label' => 'Settimana '.$w->week_number.($w->is_deload ? ' (deload)' : ''),
        ])->values()->toArray();

        $today = Carbon::today();
        $currentWeek = $mesocycle->weeks->first(
            fn (MicrocycleWeek $w) => $today->between($w->start_date, $w->end_date)
        );

        // Fallback: prima settimana con sessioni non completate, poi prima assoluta
        if ($currentWeek === null) {
            $currentWeek = $mesocycle->weeks->first(
                fn (MicrocycleWeek $w) => $w->sessions()->where('status', '!=', 'completed')->exists()
            ) ?? $mesocycle->weeks->first();
        }

        $this->selectedWeekId = $currentWeek?->id;
        $this->loadVolume();
    }

    public function updatedSelectedWeekId(): void
    {
        $this->loadVolume();
    }

    private function loadVolume(): void
    {
        if ($this->selectedWeekId === null) {
            $this->volumeData = [];
            $this->intensityMap = [];

            return;
        }

        // Verifica ownership: la settimana deve appartenere al mesociclo attivo dell'atleta
        $week = MicrocycleWeek::whereHas(
            'mesocycle', fn ($q) => $q->where('athlete_id', auth()->id())
        )->find($this->selectedWeekId);

        if ($week === null) {
            $this->volumeData = [];
            $this->intensityMap = [];

            return;
        }

        $calc = app(WeeklyVolumeCalculator::class);
        $this->volumeData = $calc->calculate(auth()->id(), $this->selectedWeekId);
        $this->intensityMap = $this->buildIntensityMap($this->volumeData);
    }

    /**
     * Converte volume data in classi CSS intensity-0..intensity-5 per ogni slug.
     *
     * Logica:
     *  - Se ha landmarks: ratio = hard_sets / mav_max; scale 0-5 mappata su below_mev/in_mav/over_mrv.
     *  - Se no landmarks (status no_landmark): scala assoluta 1 set ≈ 12% intensità.
     *    intensity-1 = 1-2 set, intensity-2 = 3-4, intensity-3 = 5-7, intensity-4 = 8-10, intensity-5 = 11+.
     *
     * @param  array<string, array{hard_sets: float, mev: int|null, mav_min: int|null, mav_max: int|null, mrv: int|null, status: string}>  $volumeData
     * @return array<string, string>
     */
    private function buildIntensityMap(array $volumeData): array
    {
        $map = [];

        foreach ($volumeData as $slug => $data) {
            $hs = $data['hard_sets'];

            if ($data['status'] === 'no_landmark' || $data['mav_max'] === null) {
                $map[$slug] = match (true) {
                    $hs <= 0 => 'intensity-0',
                    $hs <= 2 => 'intensity-1',
                    $hs <= 4 => 'intensity-2',
                    $hs <= 7 => 'intensity-3',
                    $hs <= 10 => 'intensity-4',
                    default => 'intensity-5',
                };

                continue;
            }

            $mavMax = (int) $data['mav_max'];
            $mrv = (int) $data['mrv'];
            $mev = (int) $data['mev'];

            $map[$slug] = match (true) {
                $hs <= 0 => 'intensity-0',
                $hs < $mev => 'intensity-1',      // sotto MEV
                $hs < $data['mav_min'] => 'intensity-2',  // tra MEV e MAV min (approaching)
                $hs <= $mavMax => 'intensity-3',       // in MAV
                $hs <= $mrv => 'intensity-4',       // tra MAV e MRV (approaching MRV)
                default => 'intensity-5',       // oltre MRV
            };
        }

        return $map;
    }

    /**
     * Restituisce il nome italiano del muscolo dato lo slug.
     * Usato dalla view per evitare query aggiuntive.
     */
    public static function muscleName(string $slug): string
    {
        return match ($slug) {
            'pectoralis_major_sternal' => 'Gran pettorale (sternale)',
            'pectoralis_major_clavicular' => 'Gran pettorale (clavicolare)',
            'deltoid_anterior' => 'Deltoide anteriore',
            'deltoid_lateral' => 'Deltoide laterale',
            'deltoid_posterior' => 'Deltoide posteriore',
            'triceps_brachii' => 'Tricipite',
            'biceps_brachii' => 'Bicipite',
            'brachialis' => 'Brachiale',
            'brachioradialis' => 'Brachioradiale',
            'forearm_flexors' => 'Flessori avambraccio',
            'latissimus_dorsi' => 'Gran dorsale',
            'trapezius_upper' => 'Trapezio superiore',
            'trapezius_middle' => 'Trapezio medio',
            'trapezius_lower' => 'Trapezio inferiore',
            'rhomboids' => 'Romboidi',
            'erector_spinae' => 'Erettori spinali',
            'quadriceps' => 'Quadricipite',
            'hamstrings' => 'Ischiocrurali',
            'gluteus_maximus' => 'Grande gluteo',
            'gluteus_medius' => 'Medio gluteo',
            'adductors' => 'Adduttori',
            'gastrocnemius' => 'Gastrocnemio',
            'soleus' => 'Soleo',
            'rectus_abdominis' => 'Retto addome',
            'obliques' => 'Obliqui',
            'transverse_abdominis' => 'Trasverso addome',
            default => $slug,
        };
    }

    public function render(): View
    {
        return view('livewire.athlete.weekly-volume')
            ->layout('layouts.athlete');
    }
}
