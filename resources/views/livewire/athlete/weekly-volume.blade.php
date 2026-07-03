<div class="weekly-volume-page" x-data="weeklyVolumePage()">

    <div class="page-header-row">
        <h1 class="page-title">Volume settimanale</h1>
    </div>

    @if (empty($weeks))
        <div class="wv-empty-state">
            <p>Nessun mesociclo attivo. Il trainer deve assegnarti un programma.</p>
        </div>
    @else

        {{-- Selettore settimana --}}
        <div class="wv-week-selector">
            <label for="wv-week-select" class="wv-week-label">Settimana</label>
            <select id="wv-week-select"
                    wire:model.live="selectedWeekId"
                    class="wv-select">
                @foreach ($weeks as $week)
                    <option value="{{ $week['id'] }}">
                        {{ $week['label'] }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Body map --}}
        <div class="wv-body-map-wrapper">
            @include('livewire.athlete.partials.body-map', ['intensityMap' => $intensityMap])
        </div>

        {{-- Legenda intensità --}}
        <div class="wv-legend" aria-label="Legenda colori volume">
            <span class="wv-legend-item"><span class="wv-dot intensity-0"></span>Nessuno</span>
            <span class="wv-legend-item"><span class="wv-dot intensity-1"></span>Sotto MEV</span>
            <span class="wv-legend-item"><span class="wv-dot intensity-2"></span>Tra MEV-MAV</span>
            <span class="wv-legend-item"><span class="wv-dot intensity-3"></span>In MAV</span>
            <span class="wv-legend-item"><span class="wv-dot intensity-4"></span>Vicino MRV</span>
            <span class="wv-legend-item"><span class="wv-dot intensity-5"></span>Oltre MRV</span>
        </div>

        {{-- Barre per muscolo --}}
        @if (empty($volumeData))
            <p class="wv-no-data">Nessuna sessione completata in questa settimana.</p>
        @else
            <div class="wv-bars-list">
                @foreach ($volumeData as $slug => $data)
                    <div id="muscle-bar-{{ $slug }}"
                         class="wv-bar-row"
                         data-muscle="{{ $slug }}"
                         @scroll-to-bar.window="if ($event.detail.slug === '{{ $slug }}') { $el.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); $el.classList.add('muscle-bar-highlighted'); setTimeout(() => $el.classList.remove('muscle-bar-highlighted'), 1800); }">

                        <div class="wv-bar-header">
                            <span class="wv-muscle-name">{{ \App\Livewire\Athlete\WeeklyVolume::muscleName($slug) }}</span>
                            <span class="wv-hard-sets">{{ number_format($data['hard_sets'], 1) }} set</span>
                        </div>

                        <div class="wv-bar-track" aria-label="Volume {{ $slug }}">
                            @php
                                $maxDisplay = max($data['mrv'] ?? 24, $data['hard_sets'] + 2, 24);
                                $pct = fn(float $v) => min(100, round($v / $maxDisplay * 100));
                                $statusClass = match($data['status']) {
                                    'in_mav', 'approaching_mrv' => 'bar-fill-green',
                                    'over_mrv'  => 'bar-fill-red',
                                    'below_mev' => 'bar-fill-yellow',
                                    default     => 'bar-fill-grey',
                                };
                            @endphp

                            {{-- Barra fill --}}
                            <div class="wv-bar-fill {{ $statusClass }}"
                                 style="width: {{ $pct($data['hard_sets']) }}%"
                                 role="progressbar"
                                 aria-valuenow="{{ $data['hard_sets'] }}"
                                 aria-valuemin="0"
                                 aria-valuemax="{{ $maxDisplay }}">
                            </div>

                            @if ($data['mev'] !== null)
                                {{-- Marker MEV --}}
                                <div class="wv-marker wv-marker-mev" style="left: {{ $pct($data['mev']) }}%" title="MEV {{ $data['mev'] }}">
                                    <span class="wv-marker-label">MEV</span>
                                </div>

                                {{-- Banda MAV --}}
                                @php
                                    $mavLeft  = $pct($data['mav_min']);
                                    $mavWidth = $pct($data['mav_max']) - $mavLeft;
                                @endphp
                                <div class="wv-mav-band" style="left: {{ $mavLeft }}%; width: {{ $mavWidth }}%" title="MAV {{ $data['mav_min'] }}–{{ $data['mav_max'] }}"></div>

                                {{-- Marker MRV --}}
                                <div class="wv-marker wv-marker-mrv" style="left: {{ $pct($data['mrv']) }}%" title="MRV {{ $data['mrv'] }}">
                                    <span class="wv-marker-label">MRV</span>
                                </div>
                            @endif
                        </div>

                        <div class="wv-bar-footer">
                            @if ($data['mev'] !== null)
                                <span class="wv-lm-text">MEV {{ $data['mev'] }} · MAV {{ $data['mav_min'] }}–{{ $data['mav_max'] }} · MRV {{ $data['mrv'] }}</span>
                            @else
                                <span class="wv-lm-text wv-lm-none">Nessun landmark definito</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    @endif

</div>

<script>
function weeklyVolumePage() {
    return {};
}
</script>
