<div>
    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('error') }}
        </div>
    @endif

    {{-- Segnale deload --}}
    @if ($deloadSignal && $deloadSignal->isDeloadNeeded())
        <div class="callout callout-danger">
            <h5><i class="fas fa-exclamation-triangle mr-1"></i> Deload consigliato</h5>
            <p class="mb-1">{{ $deloadSignal->notes }}</p>
            <small class="text-muted">Trigger attivi: {{ implode(', ', $deloadSignal->activeTriggers) }}</small>
        </div>
    @endif

    {{-- Selettore settimana --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h3 class="card-title mb-0">{{ $mesocycle->name }}</h3>
                <small class="text-muted">{{ $mesocycle->athlete->name }} — Trainer: {{ $mesocycle->trainer->name }}</small>
                <div class="mt-1">
                    <a href="{{ route('backoffice.athletes.profile', ['athleteId' => $mesocycle->athlete_id]) }}"
                       class="btn btn-xs btn-outline-secondary">
                        <i class="fas fa-user mr-1"></i> Vedi profilo completo atleta
                    </a>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <select wire:model.live="selectedWeekNumber" wire:change="loadVolume" class="form-control form-control-sm" style="width:auto">
                    @foreach ($mesocycle->weeks->sortBy('week_number') as $week)
                        <option value="{{ $week->week_number }}">
                            Settimana {{ $week->week_number }}
                            @if ($week->is_deload) (deload) @endif
                        </option>
                    @endforeach
                </select>
                @feature('periodization_engine')
                <button wire:click="applyProgression"
                        wire:confirm="Applicare la progressione alla settimana {{ $selectedWeekNumber + 1 }}?"
                        class="btn btn-success btn-sm">
                    <i class="fas fa-arrow-up mr-1"></i> Applica progressione sett. {{ $selectedWeekNumber + 1 }}
                </button>
                @endfeature
                <button wire:click="forceDeload"
                        wire:confirm="Forzare il deload sulla settimana {{ $selectedWeekNumber + 1 }}?"
                        class="btn btn-warning btn-sm">
                    <i class="fas fa-bed mr-1"></i> Forza deload
                </button>
            </div>
        </div>

        {{-- Risultato ultima progressione --}}
        @if ($lastProgressionResult)
            <div class="card-body border-bottom py-2">
                <small class="text-muted">
                    Ultima progressione:
                    <strong>{{ match($lastProgressionResult->action) {
                        'progressed' => 'Volume aumentato',
                        'held'       => 'Volume mantenuto',
                        'reduced'    => 'Volume ridotto',
                        'deload'     => 'Deload applicato',
                        default      => $lastProgressionResult->action,
                    } }}</strong>
                    @if ($lastProgressionResult->note)
                        — {{ $lastProgressionResult->note }}
                    @endif
                </small>
            </div>
        @endif

        {{-- Tabella volume per muscolo --}}
        <div class="card-body p-0">
            @if (empty($volumeData))
                <div class="p-3 text-muted">Nessuna sessione completata in questa settimana.</div>
            @else
                <table class="table table-sm table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Muscolo</th>
                            <th class="text-center">Hard set</th>
                            <th class="text-center">MEV</th>
                            <th class="text-center">MAV</th>
                            <th class="text-center">MRV</th>
                            <th style="min-width:160px">Stato</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($volumeData as $slug => $data)
                            @php
                                $barClass = match($data['status']) {
                                    'in_mav'         => 'bg-success',
                                    'approaching_mrv' => 'bg-warning',
                                    'over_mrv'       => 'bg-danger',
                                    'below_mev'      => 'bg-secondary',
                                    default          => 'bg-light',
                                };
                                $pct = $data['mrv'] ? min(100, round(($data['hard_sets'] / $data['mrv']) * 100)) : 0;
                                $muscleName = \App\Models\Muscle::where('slug', $slug)->value('name_it') ?? $slug;
                            @endphp
                            <tr>
                                <td class="align-middle">{{ $muscleName }}</td>
                                <td class="text-center align-middle">{{ number_format($data['hard_sets'], 1) }}</td>
                                <td class="text-center align-middle text-muted">{{ $data['mev'] ?? '—' }}</td>
                                <td class="text-center align-middle text-muted">
                                    @if ($data['mav_min'] ?? null)
                                        {{ $data['mav_min'] }}–{{ $data['mav_max'] }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-center align-middle text-muted">{{ $data['mrv'] ?? '—' }}</td>
                                <td class="align-middle">
                                    <div class="progress" style="height:16px">
                                        <div class="progress-bar {{ $barClass }}" style="width:{{ $pct }}%">
                                            <small>{{ match($data['status']) {
                                                'in_mav'         => 'In MAV',
                                                'approaching_mrv' => 'Vicino MRV',
                                                'over_mrv'       => 'Over MRV',
                                                'below_mev'      => 'Sotto MEV',
                                                default          => $data['status'],
                                            } }}</small>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
