@extends('adminlte::page')

@section('title', 'Analytics — ' . $athlete->name)

@section('content_header')
    <h1>Analytics atleta — {{ $athlete->name }}</h1>
@stop

@section('content')
    <div class="row">
        {{-- Card peso --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Peso (ultimi 90 giorni)</h3>
                </div>
                <div class="card-body">
                    @if (count($weightChartData['data']) > 0)
                        <canvas id="weightChart" height="120"></canvas>
                    @else
                        <p class="text-muted text-center py-3">Nessuna misurazione disponibile.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Card e1RM top 5 --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Top 5 esercizi — e1RM (ultimi 30 giorni)</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Esercizio</th>
                                <th>Max e1RM</th>
                                <th>Sessioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($e1rmRows as $row)
                                <tr>
                                    <td>{{ $row['exercise_name'] }}</td>
                                    <td>{{ $row['max_e1rm'] !== null ? number_format($row['max_e1rm'], 1).' kg' : '—' }}</td>
                                    <td>{{ $row['sessions_count'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Nessun dato disponibile</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Card volume settimana corrente --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Volume settimana corrente (hard sets per muscolo)</h3>
        </div>
        <div class="card-body p-0">
            @if (count($volumeRows) > 0)
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Muscolo</th>
                            <th>Hard sets</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($volumeRows as $row)
                            <tr>
                                <td>{{ $row['muscle'] }}</td>
                                <td>{{ number_format($row['hard_sets'], 1) }}</td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'below_mev' => 'secondary',
                                            'in_mav' => 'success',
                                            'above_mav' => 'warning',
                                            'at_mrv' => 'danger',
                                            'no_landmark' => 'light',
                                        ];
                                        $color = $statusColors[$row['status']] ?? 'secondary';
                                    @endphp
                                    <span class="badge badge-{{ $color }}">{{ $row['status'] }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted text-center py-3">Nessun mesociclo attivo o settimana in corso.</p>
            @endif
        </div>
    </div>

    {{-- Card confronto fotografico --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Confronto fotografico</h3>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label>Data 1</label>
                    <select class="form-control form-control-sm" wire:model.live="photoDate1">
                        <option value="">Seleziona data...</option>
                        @foreach ($photoDates as $date)
                            <option value="{{ $date }}">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Data 2</label>
                    <select class="form-control form-control-sm" wire:model.live="photoDate2">
                        <option value="">Seleziona data...</option>
                        @foreach ($photoDates as $date)
                            <option value="{{ $date }}">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @php
                $poses = ['front' => 'Fronte', 'back' => 'Retro', 'side_left' => 'Fianco SX', 'side_right' => 'Fianco DX'];
            @endphp

            @if ($photoDate1 || $photoDate2)
                <div class="row">
                    @foreach ($poses as $pose => $poseLabel)
                        <div class="col-md-3 text-center">
                            <p class="text-muted text-xs mb-1">{{ $poseLabel }}</p>
                            <div class="d-flex justify-content-around">
                                <div>
                                    @if (!empty($photos1[$pose]))
                                        <img src="{{ route('athlete.photos.show', $photos1[$pose]['id']) }}"
                                             style="width:100px;height:130px;object-fit:cover;border-radius:4px;"
                                             alt="{{ $poseLabel }}">
                                        <small class="d-block text-muted">{{ $photoDate1 ? \Carbon\Carbon::parse($photoDate1)->format('d/m/Y') : '' }}</small>
                                    @elseif ($photoDate1)
                                        <div style="width:100px;height:130px;background:#f4f6f9;border-radius:4px;display:flex;align-items:center;justify-content:center;">
                                            <small class="text-muted">—</small>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    @if (!empty($photos2[$pose]))
                                        <img src="{{ route('athlete.photos.show', $photos2[$pose]['id']) }}"
                                             style="width:100px;height:130px;object-fit:cover;border-radius:4px;"
                                             alt="{{ $poseLabel }}">
                                        <small class="d-block text-muted">{{ $photoDate2 ? \Carbon\Carbon::parse($photoDate2)->format('d/m/Y') : '' }}</small>
                                    @elseif ($photoDate2)
                                        <div style="width:100px;height:130px;background:#f4f6f9;border-radius:4px;display:flex;align-items:center;justify-content:center;">
                                            <small class="text-muted">—</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted">Seleziona due date per confrontare le foto.</p>
            @endif
        </div>
    </div>
@stop

@section('plugins.js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('weightChart');
        if (!ctx) return;
        const labels = @json($weightChartData['labels']);
        const data = @json($weightChartData['data']);
        if (!labels.length) return;
        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Peso (kg)',
                    data,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0,123,255,0.08)',
                    tension: 0.3,
                    pointRadius: 3,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: false } }
            }
        });
    });
</script>
@stop

@section('plugins.Livewire', true)
