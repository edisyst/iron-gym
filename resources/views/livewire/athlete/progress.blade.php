<div x-data="{ tab: @entangle('activeTab') }">
    <h2 style="font-size:20px;font-weight:700;margin-bottom:16px;">Progressi</h2>

    {{-- Tab navigation --}}
    <div class="ig-tab-group">
        <button type="button" @click="tab='body'; $wire.set('activeTab','body')"
                class="ig-tab" :class="{ 'ig-tab--active': tab==='body' }">
            Corpo
        </button>
        <button type="button" @click="tab='strength'; $wire.set('activeTab','strength')"
                class="ig-tab" :class="{ 'ig-tab--active': tab==='strength' }">
            Forza
        </button>
        <button type="button" @click="tab='volume'; $wire.set('activeTab','volume'); $wire.loadVolumeData()"
                class="ig-tab" :class="{ 'ig-tab--active': tab==='volume' }">
            Volume
        </button>
    </div>

    {{-- Tab Corpo --}}
    <div x-show="tab==='body'">
        <div class="athlete-card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <p class="section-title" style="margin-bottom:0;">PESO (ultimi 90 giorni)</p>
                <a href="{{ route('athlete.measurements') }}"
                   style="font-size:12px;color:#FF6B00;text-decoration:none;">+ Aggiungi</a>
            </div>

            @if (count($weightChartData['data']) > 0)
                @php
                    $lastIdx = count($weightChartData['data']) - 1;
                    $prevIdx = $lastIdx - 1;
                    $lastWeight = $weightChartData['data'][$lastIdx];
                    $delta = $prevIdx >= 0 ? round($lastWeight - $weightChartData['data'][$prevIdx], 1) : null;
                @endphp
                <div style="margin-bottom:12px;">
                    <span style="font-size:32px;font-weight:700;color:#fff;">{{ number_format($lastWeight, 1) }}</span>
                    <span style="font-size:16px;color:#888;margin-left:4px;">kg</span>
                    @if ($delta !== null)
                        <span style="font-size:14px;margin-left:12px;color:{{ $delta < 0 ? '#22c55e' : ($delta > 0 ? '#ef4444' : '#888') }};">
                            {{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 1) }} kg
                        </span>
                    @endif
                </div>
                <div style="position:relative;height:180px;">
                    <canvas id="weightChart"></canvas>
                </div>
            @else
                <p style="color:#888;font-size:14px;text-align:center;padding:24px 0;">
                    Nessun dato. <a href="{{ route('athlete.measurements') }}" style="color:#FF6B00;">Aggiungi la prima misurazione.</a>
                </p>
            @endif
        </div>
    </div>

    {{-- Tab Forza --}}
    <div x-show="tab==='strength'">
        <div class="athlete-card">
            <p class="section-title">E1RM STIMATO (formula Epley)</p>

            <select wire:change="loadE1rmData($event.target.value)"
                    style="background:#2A2A2A;border:1px solid #333;border-radius:6px;color:#fff;
                           padding:8px 10px;width:100%;font-size:14px;margin-bottom:16px;">
                <option value="">Seleziona esercizio...</option>
                @foreach ($exercises as $ex)
                    <option value="{{ $ex['id'] }}" {{ $selectedExerciseId == $ex['id'] ? 'selected' : '' }}>
                        {{ $ex['name_it'] }}
                    </option>
                @endforeach
            </select>

            @if ($selectedExerciseId && count($e1rmChartData['data']) > 0)
                @if ($e1rmChartData['pr'] !== null)
                    <div style="margin-bottom:12px;">
                        <span style="font-size:12px;color:#888;">PR stimato: </span>
                        <span style="font-size:24px;font-weight:700;color:#FF6B00;">
                            {{ number_format($e1rmChartData['pr'], 1) }} kg
                        </span>
                    </div>
                @endif
                <div style="position:relative;height:200px;">
                    <canvas id="e1rmChart"></canvas>
                </div>
            @elseif ($selectedExerciseId)
                <p style="color:#888;font-size:14px;text-align:center;padding:24px 0;">
                    Nessun dato per questo esercizio.
                </p>
            @else
                <p style="color:#888;font-size:14px;text-align:center;padding:24px 0;">
                    Seleziona un esercizio per vedere l'andamento della forza.
                </p>
            @endif
        </div>
    </div>

    {{-- Tab Volume --}}
    <div x-show="tab==='volume'">
        <div class="athlete-card">
            <p class="section-title">VOLUME SETTIMANALE PER GRUPPO MUSCOLARE (ultimi 6 mesi)</p>

            @if (count($volumeChartData['labels']) > 0)
                <div style="position:relative;height:220px;">
                    <canvas id="volumeChart"></canvas>
                </div>
            @else
                <p style="color:#888;font-size:14px;text-align:center;padding:24px 0;">
                    Nessun dato di allenamento disponibile.
                </p>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    // Colori dataset volume per gruppo muscolare
    const volumeColors = ['#FF6B00','#3b82f6','#22c55e','#a855f7','#eab308','#06b6d4'];

    let weightChart = null;
    let e1rmChart = null;
    let volumeChart = null;

    function renderWeightChart() {
        const ctx = document.getElementById('weightChart');
        if (!ctx) return;
        const labels = @json($weightChartData['labels']);
        const data = @json($weightChartData['data']);
        if (!labels.length) return;
        if (weightChart) weightChart.destroy();
        weightChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Peso (kg)',
                    data,
                    borderColor: '#FF6B00',
                    backgroundColor: 'rgba(255,107,0,0.08)',
                    tension: 0.3,
                    pointRadius: 4,
                    pointBackgroundColor: '#FF6B00',
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: '#888', font: { size: 11 } }, grid: { color: '#2A2A2A' } },
                    y: { ticks: { color: '#888', font: { size: 11 } }, grid: { color: '#2A2A2A' } },
                }
            }
        });
    }

    function renderE1rmChart() {
        const ctx = document.getElementById('e1rmChart');
        if (!ctx) return;
        const labels = @json($e1rmChartData['labels']);
        const data = @json($e1rmChartData['data']);
        const pr = @json($e1rmChartData['pr']);
        if (!labels.length) return;
        if (e1rmChart) e1rmChart.destroy();

        const datasets = [{
            label: 'e1RM Epley (kg)',
            data,
            borderColor: '#FF6B00',
            backgroundColor: 'rgba(255,107,0,0.08)',
            tension: 0.3,
            pointRadius: 4,
            pointBackgroundColor: '#FF6B00',
            fill: true,
        }];

        // Linea PR orizzontale tratteggiata
        if (pr) {
            datasets.push({
                label: 'PR',
                data: labels.map(() => pr),
                borderColor: '#22c55e',
                borderDash: [6, 3],
                pointRadius: 0,
                borderWidth: 1.5,
                fill: false,
            });
        }

        e1rmChart = new Chart(ctx, {
            type: 'line',
            data: { labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: '#888', font: { size: 11 } }, grid: { color: '#2A2A2A' } },
                    y: { ticks: { color: '#888', font: { size: 11 } }, grid: { color: '#2A2A2A' } },
                }
            }
        });
    }

    function renderVolumeChart() {
        const ctx = document.getElementById('volumeChart');
        if (!ctx) return;
        const labels = @json($volumeChartData['labels']);
        const datasets = (@json($volumeChartData['datasets'])).map((ds, i) => ({
            ...ds,
            backgroundColor: volumeColors[i % volumeColors.length],
            stack: 'volume',
        }));
        if (!labels.length) return;
        if (volumeChart) volumeChart.destroy();
        volumeChart = new Chart(ctx, {
            type: 'bar',
            data: { labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { color: '#ccc', font: { size: 11 }, boxWidth: 12 }
                    }
                },
                scales: {
                    x: { stacked: true, ticks: { color: '#888', font: { size: 10 } }, grid: { color: '#2A2A2A' } },
                    y: { stacked: true, ticks: { color: '#888', font: { size: 11 } }, grid: { color: '#2A2A2A' } },
                }
            }
        });
    }

    // Inizializzazione al caricamento pagina
    document.addEventListener('DOMContentLoaded', () => {
        renderWeightChart();
    });

    // Aggiornamento dopo dispatch Livewire
    Livewire.on('e1rmDataLoaded', () => {
        // Piccolo delay per lasciare Livewire aggiornare il DOM
        setTimeout(renderE1rmChart, 50);
    });

    Livewire.on('volumeDataLoaded', () => {
        setTimeout(renderVolumeChart, 50);
    });

    // Re-render dopo navigazione Livewire (es. tab switch)
    document.addEventListener('livewire:navigated', () => {
        renderWeightChart();
    });
</script>
@endpush
