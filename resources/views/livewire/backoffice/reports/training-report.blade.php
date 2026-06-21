<div>
    {{-- Filtri --}}
    <div class="card card-outline card-primary mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-auto">
                    <label class="mb-0 mr-1">Dal</label>
                    <input type="date" class="form-control form-control-sm d-inline-block w-auto" wire:model.live="dateFrom">
                </div>
                <div class="col-auto">
                    <label class="mb-0 mr-1">Al</label>
                    <input type="date" class="form-control form-control-sm d-inline-block w-auto" wire:model.live="dateTo">
                </div>
                <div class="col-auto">
                    <select class="form-control form-control-sm" wire:model.live="mesoStatus">
                        <option value="all">Tutti i mesocicli</option>
                        <option value="active">Solo attivi</option>
                        <option value="completed">Solo completati</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabella atleti --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Atleti</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>Atleta</th>
                        <th>Mesociclo attivo</th>
                        <th>Sessioni completate</th>
                        <th>Sessioni saltate</th>
                        <th>Adherence %</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($athleteRows as $row)
                        <tr>
                            <td>{{ $row->nome }}</td>
                            <td>{{ $row->mesociclo ?? '—' }}</td>
                            <td>{{ $row->sessioni_completate }}</td>
                            <td>{{ $row->sessioni_saltate }}</td>
                            <td>
                                @php $pct = $row->adherence_rate; @endphp
                                <span class="badge badge-{{ $pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger') }}">
                                    {{ $pct }}%
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-xs btn-outline-info" wire:click="openDrilldown({{ $row->athlete_id }})">
                                    Dettaglio
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">Nessun dato nel periodo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Drilldown atleta --}}
    @if ($drilldown !== null)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Dettaglio — {{ $drilldown['athlete_name'] }}</h3>
                <div class="card-tools">
                    <button class="btn btn-xs btn-secondary" wire:click="closeDrilldown">Chiudi</button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-7">
                        <h6>Sessioni completate per settimana (ultimi 8 mesocicli)</h6>
                        @if (!empty($drilldown['weekly_sessions']))
                            <canvas id="weeklyChart" height="120"></canvas>
                        @else
                            <p class="text-muted">Nessuna sessione registrata.</p>
                        @endif
                    </div>
                    <div class="col-md-5">
                        <h6>Ultimi 5 feedback</h6>
                        @if (!empty($drilldown['feedbacks']))
                            @php
                                $badgeClass = fn($v) => match((int) $v) {
                                    0 => 'secondary',
                                    1 => 'success',
                                    2 => 'warning',
                                    3 => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <table class="table table-xs table-sm">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Pump</th>
                                        <th>Indolenz.</th>
                                        <th>Sforzo</th>
                                        <th>Dolore art.</th>
                                        <th>Performance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($drilldown['feedbacks'] as $fb)
                                        <tr>
                                            <td>{{ $fb->scheduled_date ? \Carbon\Carbon::parse($fb->scheduled_date)->format('d/m') : '—' }}</td>
                                            @foreach (['pump', 'soreness_prev', 'perceived_effort', 'joint_pain', 'performance'] as $field)
                                                <td>
                                                    @if ($fb->$field !== null)
                                                        <span class="badge badge-{{ $badgeClass($fb->$field) }}">{{ $fb->$field }}</span>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-muted">Nessun feedback.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @push('js')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
        <script>
            (function () {
                const weekly = @json($drilldown['weekly_sessions']);
                const ctx = document.getElementById('weeklyChart');
                if (!ctx || !Object.keys(weekly).length) return;
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: Object.keys(weekly),
                        datasets: [{
                            label: 'Sessioni completate',
                            data: Object.values(weekly),
                            backgroundColor: 'rgba(0,123,255,0.6)',
                            borderColor: '#007bff',
                            borderWidth: 1,
                        }]
                    },
                    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
                });
            })();
        </script>
        @endpush
    @endif
</div>
