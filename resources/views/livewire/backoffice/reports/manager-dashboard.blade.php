<div>
@feature('financial_reports')
    {{-- Selettore date --}}
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
            </div>
        </div>
    </div>

    {{-- Info-box KPI --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fas fa-euro-sign"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Fatturato periodo</span>
                    <span class="info-box-number">€ {{ $revenueEuro }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-box">
                <span class="info-box-icon bg-info"><i class="fas fa-user-plus"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Nuovi iscritti</span>
                    <span class="info-box-number">{{ $newMembers }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-box">
                <span class="info-box-icon bg-warning"><i class="fas fa-sync-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Retention rate</span>
                    <span class="info-box-number">{{ $retentionRate }}%</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-box">
                <span class="info-box-icon bg-danger"><i class="fas fa-user-times"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Churn rate</span>
                    <span class="info-box-number">{{ $churnRate }}%</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Grafici --}}
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Fatturato mensile (ultimi 12 mesi)</h3>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Revenue per piano</h3>
                </div>
                <div class="card-body">
                    <canvas id="planChart" height="180"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Occupancy trainer</h3>
                </div>
                <div class="card-body">
                    <canvas id="occupancyChart" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabella revenue per trainer --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Fatturato per trainer</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-striped mb-0">
                <thead>
                    <tr>
                        <th>Trainer</th>
                        <th>N. atleti gestiti</th>
                        <th>Fatturato attribuito</th>
                        <th>Occupancy %</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trainerRevenue as $row)
                        @php
                            $occ = collect($trainerOccupancy)->firstWhere('trainer', $row['trainer']);
                        @endphp
                        <tr>
                            <td>{{ $row['trainer'] }}</td>
                            <td>{{ $row['member_count'] }}</td>
                            <td>€ {{ number_format($row['revenue_cents'] / 100, 2, ',', '.') }}</td>
                            <td>{{ $occ ? $occ['occupancy_pct'].'%' : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">Nessun dato nel periodo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tabella tesserati a rischio churn --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Tesserati a rischio churn (abbonamento scaduto 0-30 giorni, non rinnovato)</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-striped mb-0">
                <thead>
                    <tr>
                        <th>Tesserato</th>
                        <th>Scadenza abbonamento</th>
                        <th>Ultimo accesso</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($atRiskMembers as $m)
                        <tr>
                            <td>{{ $m->nome }}</td>
                            <td>{{ \Carbon\Carbon::parse($m->expires_at)->format('d/m/Y') }}</td>
                            <td>{{ $m->last_access ? \Carbon\Carbon::parse($m->last_access)->format('d/m/Y') : 'mai' }}</td>
                            <td>
                                <a href="{{ route('backoffice.athletes.messages', $m->member_id) }}" class="btn btn-sm btn-outline-primary">
                                    Contatta
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">Nessun tesserato a rischio.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const revenueData = @json($revenueChart);
            const planData = @json($planRevenue);
            const occupancyData = @json($trainerOccupancy);

            // Fatturato mensile
            new Chart(document.getElementById('revenueChart'), {
                type: 'bar',
                data: {
                    labels: Object.keys(revenueData),
                    datasets: [{
                        label: 'Fatturato (€)',
                        data: Object.values(revenueData).map(v => v / 100),
                        backgroundColor: 'rgba(40,167,69,0.6)',
                        borderColor: '#28a745',
                        borderWidth: 1,
                    }]
                },
                options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
            });

            // Revenue per piano (donut)
            if (planData.length) {
                new Chart(document.getElementById('planChart'), {
                    type: 'doughnut',
                    data: {
                        labels: planData.map(r => r.plan),
                        datasets: [{ data: planData.map(r => r.revenue_cents / 100), backgroundColor: ['#007bff','#28a745','#ffc107','#dc3545','#6f42c1'] }]
                    },
                    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
                });
            }

            // Occupancy trainer (barre orizzontali)
            if (occupancyData.length) {
                new Chart(document.getElementById('occupancyChart'), {
                    type: 'bar',
                    data: {
                        labels: occupancyData.map(r => r.trainer),
                        datasets: [{
                            label: 'Occupancy %',
                            data: occupancyData.map(r => r.occupancy_pct),
                            backgroundColor: 'rgba(0,123,255,0.6)',
                            borderColor: '#007bff',
                            borderWidth: 1,
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: { x: { beginAtZero: true, max: 100 } }
                    }
                });
            }
        })();
    </script>
    @endpush
@else
    <div class="alert alert-warning">
        <i class="fas fa-lock mr-1"></i> Report finanziari non disponibili.
    </div>
@endfeature
</div>
