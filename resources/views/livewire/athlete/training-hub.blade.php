<div x-data="{ mainTab: @entangle('mainTab'), progressTab: @entangle('progressTab') }">

    {{-- Tab esterno: Storico / Progressi --}}
    <div style="display:flex;gap:0;margin-bottom:20px;background:#1E1E1E;border-radius:10px;padding:4px;">
        <button type="button"
                @click="mainTab = 'storico'; $wire.set('mainTab', 'storico')"
                :style="mainTab === 'storico' ? 'background:#FF6B00;color:#fff;' : 'background:transparent;color:#888;'"
                style="flex:1;border:none;border-radius:8px;padding:9px;font-size:14px;font-weight:600;cursor:pointer;transition:all 0.15s;">
            Storico
        </button>
        <button type="button"
                @click="mainTab = 'progress'; $wire.switchToProgress()"
                :style="mainTab === 'progress' ? 'background:#FF6B00;color:#fff;' : 'background:transparent;color:#888;'"
                style="flex:1;border:none;border-radius:8px;padding:9px;font-size:14px;font-weight:600;cursor:pointer;transition:all 0.15s;">
            Progressi
        </button>
    </div>

    {{-- ==================== TAB STORICO ==================== --}}
    <div x-show="mainTab === 'storico'" x-cloak>

        <div class="athlete-card" style="padding:12px 14px;margin-bottom:16px;">
            <select wire:model.live="mesocycleId"
                    style="background:#2A2A2A;border:1px solid #333;border-radius:6px;
                           color:#fff;padding:8px 10px;width:100%;font-size:14px;">
                <option value="">Tutti i mesocicli</option>
                @foreach ($mesocycles as $meso)
                    <option value="{{ $meso->id }}">{{ $meso->name }}</option>
                @endforeach
            </select>
        </div>

        @forelse ($sessions as $session)
            <div class="athlete-card" style="margin-bottom:12px;">
                <div wire:click="showDetail({{ $session->id }})"
                     style="cursor:pointer;display:flex;align-items:center;gap:12px;">
                    <div style="flex:1;min-width:0;">
                        <p style="font-size:15px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ $session->name }}
                        </p>
                        <p style="font-size:12px;color:#666;margin-top:3px;">
                            {{ $session->completed_at?->format('d/m/Y') }}
                            @php $dur = $this->duration($session); @endphp
                            @if ($dur) &bull; {{ $dur }} @endif
                            &bull; {{ $session->week->mesocycle->name }}
                            &bull; {{ $this->completedSetsCount($session) }} set
                        </p>
                    </div>
                    <svg style="width:18px;height:18px;color:#555;flex-shrink:0;
                         transition:transform .2s;{{ $selectedSessionId === $session->id ? 'transform:rotate(90deg)' : '' }}"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>

                @if ($selectedSessionId === $session->id && $this->selectedSession !== null)
                    <div style="margin-top:16px;border-top:1px solid #2A2A2A;padding-top:16px;">
                        @foreach ($this->selectedSession->sessionExercises as $exercise)
                            <div style="margin-bottom:16px;">
                                <button wire:click="showExerciseHistory({{ $exercise->exercise_id }}, '{{ addslashes($exercise->exercise->name_it) }}')"
                                        style="font-size:14px;font-weight:600;margin-bottom:8px;color:#ccc;
                                               background:none;border:none;padding:0;text-align:left;cursor:pointer;
                                               text-decoration:underline dotted;text-underline-offset:3px;">
                                    {{ $exercise->exercise->name_it }}
                                </button>

                                @foreach ($exercise->sets->sortBy('set_index')->whereNotNull('actual_reps') as $set)
                                    <div style="display:flex;gap:12px;font-size:13px;color:#888;
                                                padding:4px 0;border-bottom:1px solid #222;">
                                        <span style="color:#666;width:24px;">{{ $set->set_index }}</span>
                                        <span>{{ $set->actual_reps }} reps</span>
                                        @if ($set->actual_weight_kg)
                                            <span>{{ $set->actual_weight_kg }} kg</span>
                                        @endif
                                        @if ($set->actual_rir !== null)
                                            <span>RIR {{ $set->actual_rir }}</span>
                                        @endif
                                        @if ($set->estimated_1rm)
                                            <span style="color:#FF6B00;margin-left:auto;">
                                                e1RM {{ $set->estimated_1rm }} kg
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="athlete-card" style="text-align:center;padding:32px 16px;">
                <p style="color:#666;">Nessuna sessione completata.</p>
            </div>
        @endforelse

        {{-- Paginazione --}}
        @if ($sessions->hasPages())
            <div style="margin-top:16px;display:flex;gap:8px;justify-content:center;">
                @if ($sessions->onFirstPage())
                    <span style="color:#444;padding:8px 14px;border:1px solid #333;border-radius:6px;">&#8249;</span>
                @else
                    <button wire:click="previousPage" style="background:#2A2A2A;color:#fff;border:1px solid #333;
                            border-radius:6px;padding:8px 14px;cursor:pointer;">&#8249;</button>
                @endif
                <span style="color:#888;padding:8px 14px;">{{ $sessions->currentPage() }} / {{ $sessions->lastPage() }}</span>
                @if ($sessions->hasMorePages())
                    <button wire:click="nextPage" style="background:#2A2A2A;color:#fff;border:1px solid #333;
                            border-radius:6px;padding:8px 14px;cursor:pointer;">&#8250;</button>
                @else
                    <span style="color:#444;padding:8px 14px;border:1px solid #333;border-radius:6px;">&#8250;</span>
                @endif
            </div>
        @endif

        {{-- Modal storico esercizio --}}
        @if ($exerciseHistoryId !== null)
            <div style="position:fixed;inset:0;z-index:300;background:rgba(0,0,0,.8);display:flex;align-items:flex-end;">
                <div style="background:#1E1E1E;border-radius:16px 16px 0 0;padding:20px 16px;width:100%;max-height:85vh;overflow-y:auto;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                        <p style="font-size:15px;font-weight:700;color:#fff;">{{ $exerciseHistoryName }}</p>
                        <button wire:click="showExerciseHistory({{ $exerciseHistoryId }}, '')"
                                style="background:none;border:none;color:#666;font-size:22px;line-height:1;cursor:pointer;">&times;</button>
                    </div>

                    @forelse ($this->exerciseHistory as $se)
                        <div style="margin-bottom:16px;">
                            <p style="font-size:12px;color:#FF6B00;font-weight:600;margin-bottom:6px;">
                                {{ $se->session->completed_at?->format('d/m/Y') }} &bull; {{ $se->session->name }}
                            </p>
                            @foreach ($se->sets->whereNotNull('actual_reps') as $set)
                                <div style="display:flex;gap:10px;font-size:13px;color:#888;
                                            padding:3px 0;border-bottom:1px solid #222;">
                                    <span style="color:#555;width:20px;">{{ $set->set_index }}</span>
                                    <span>{{ $set->actual_reps }} reps</span>
                                    @if ($set->actual_weight_kg)
                                        <span>{{ $set->actual_weight_kg }} kg</span>
                                    @endif
                                    @if ($set->actual_rir !== null)
                                        <span>RIR {{ $set->actual_rir }}</span>
                                    @endif
                                    @if ($set->estimated_1rm)
                                        <span style="color:#FF6B00;margin-left:auto;">e1RM {{ $set->estimated_1rm }} kg</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <p style="color:#666;text-align:center;padding:24px 0;">Nessuna sessione precedente.</p>
                    @endforelse
                </div>
            </div>
        @endif

    </div>

    {{-- ==================== TAB PROGRESSI ==================== --}}
    <div x-show="mainTab === 'progress'" x-cloak>

        {{-- Sub-tab: Corpo / Forza / Volume --}}
        <div style="display:flex;gap:0;margin-bottom:20px;background:#1A1A1A;border-radius:10px;padding:4px;">
            <button type="button"
                    @click="progressTab = 'body'; $wire.set('progressTab','body')"
                    :style="progressTab==='body' ? 'background:#333;color:#fff;' : 'background:transparent;color:#666;'"
                    style="flex:1;border:none;border-radius:8px;padding:7px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.15s;">
                Corpo
            </button>
            <button type="button"
                    @click="progressTab = 'strength'; $wire.set('progressTab','strength')"
                    :style="progressTab==='strength' ? 'background:#333;color:#fff;' : 'background:transparent;color:#666;'"
                    style="flex:1;border:none;border-radius:8px;padding:7px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.15s;">
                Forza
            </button>
            <button type="button"
                    @click="progressTab = 'volume'; $wire.set('progressTab','volume'); $wire.loadVolumeData()"
                    :style="progressTab==='volume' ? 'background:#333;color:#fff;' : 'background:transparent;color:#666;'"
                    style="flex:1;border:none;border-radius:8px;padding:7px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.15s;">
                Volume
            </button>
        </div>

        {{-- Sub-tab Corpo --}}
        <div x-show="progressTab === 'body'">
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

        {{-- Sub-tab Forza --}}
        <div x-show="progressTab === 'strength'">
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

        {{-- Sub-tab Volume --}}
        <div x-show="progressTab === 'volume'">
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

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    const volumeColors = ['#FF6B00','#3b82f6','#22c55e','#a855f7','#eab308','#06b6d4'];

    let weightChart = null;
    let e1rmChart   = null;
    let volumeChart = null;

    function renderWeightChart() {
        const ctx = document.getElementById('weightChart');
        if (!ctx) return;
        const labels = @json($weightChartData['labels']);
        const data   = @json($weightChartData['data']);
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
        const data   = @json($e1rmChartData['data']);
        const pr     = @json($e1rmChartData['pr']);
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
        const labels   = @json($volumeChartData['labels']);
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

    document.addEventListener('DOMContentLoaded', () => {
        // Se la pagina carica già sul tab progressi (es. redirect futuro)
        if (@json($mainTab) === 'progress') {
            renderWeightChart();
        }
    });

    Livewire.on('weightDataLoaded', () => setTimeout(renderWeightChart, 50));
    Livewire.on('e1rmDataLoaded',   () => setTimeout(renderE1rmChart, 50));
    Livewire.on('volumeDataLoaded', () => setTimeout(renderVolumeChart, 50));

    document.addEventListener('livewire:navigated', () => {
        if (@json($mainTab) === 'progress') renderWeightChart();
    });
</script>
@endpush
