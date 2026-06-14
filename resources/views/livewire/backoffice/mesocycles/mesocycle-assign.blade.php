<div>
    {{-- Flash success --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                @if ($step === 1)
                    Step 1 di 2 &mdash; Seleziona atleta e template
                @else
                    Step 2 di 2 &mdash; Parametri mesociclo
                @endif
            </h3>
            {{-- Indicatore wizard --}}
            <div class="card-tools">
                <span class="badge badge-{{ $step === 1 ? 'primary' : 'secondary' }} mr-1">1</span>
                <span class="badge badge-{{ $step === 2 ? 'primary' : 'secondary' }}">2</span>
            </div>
        </div>
        <div class="card-body">

            {{-- ======================== STEP 1 ======================== --}}
            @if ($step === 1)
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="athleteId">Atleta <span class="text-danger">*</span></label>
                            <select wire:model.live="athleteId" id="athleteId"
                                    class="form-control @error('athleteId') is-invalid @enderror">
                                <option value="">— Seleziona atleta —</option>
                                @foreach ($athletes as $athlete)
                                    <option value="{{ $athlete->id }}">{{ $athlete->name }}</option>
                                @endforeach
                            </select>
                            @error('athleteId')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="goalFilter">Filtra template per obiettivo</label>
                            <select wire:model.live="goalFilter" id="goalFilter" class="form-control">
                                <option value="">Tutti gli obiettivi</option>
                                <option value="hypertrophy">Ipertrofia</option>
                                <option value="strength">Forza</option>
                                <option value="cut">Definizione</option>
                                <option value="recomp">Ricomposizione</option>
                                <option value="peaking">Peaking</option>
                                <option value="general">Generale</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="templateId">Template scheda <span class="text-danger">*</span></label>
                    <select wire:model.live="templateId" id="templateId"
                            class="form-control @error('templateId') is-invalid @enderror">
                        <option value="">— Seleziona template —</option>
                        @foreach ($this->templates as $tpl)
                            <option value="{{ $tpl->id }}">
                                {{ $tpl->name }}
                                ({{ $tpl->weeks_count }} sett. &bull; di {{ $tpl->creator?->name ?? '?' }})
                            </option>
                        @endforeach
                    </select>
                    @error('templateId')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Preview template --}}
                @if ($this->selectedTemplate !== null)
                    <div class="card card-outline card-info mt-3">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-clipboard-list mr-1"></i>
                                Preview: {{ $this->selectedTemplate->name }}
                            </h3>
                        </div>
                        <div class="card-body">
                            @php
                                $sessionsByWeek = $this->selectedTemplate->templateSessions->groupBy('week_number');
                            @endphp
                            @foreach ($sessionsByWeek as $weekNum => $sessions)
                                <div class="mb-3">
                                    <strong>Settimana {{ $weekNum }}</strong>
                                    <ul class="list-unstyled ml-3 mt-1">
                                        @foreach ($sessions->sortBy('order_in_week') as $ts)
                                            <li><i class="fas fa-calendar-day mr-1 text-muted"></i>{{ $ts->name }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-4">
                    <button wire:click="nextStep" class="btn btn-primary">
                        Avanti <i class="fas fa-arrow-right ml-1"></i>
                    </button>
                </div>
            @endif

            {{-- ======================== STEP 2 ======================== --}}
            @if ($step === 2)
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="name">Nome mesociclo <span class="text-danger">*</span></label>
                            <input type="text" wire:model="name" id="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   maxlength="255">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="weeksCount">Settimane <span class="text-danger">*</span></label>
                            <input type="number" wire:model="weeksCount" id="weeksCount"
                                   class="form-control @error('weeksCount') is-invalid @enderror"
                                   min="4" max="6">
                            @error('weeksCount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="goal">Obiettivo <span class="text-danger">*</span></label>
                            <select wire:model="goal" id="goal"
                                    class="form-control @error('goal') is-invalid @enderror">
                                <option value="">— Seleziona —</option>
                                <option value="hypertrophy">Ipertrofia</option>
                                <option value="strength">Forza</option>
                                <option value="cut">Definizione</option>
                                <option value="recomp">Ricomposizione</option>
                                <option value="peaking">Peaking</option>
                                <option value="general">Generale</option>
                            </select>
                            @error('goal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="periodizationModel">Modello periodizzazione <span class="text-danger">*</span></label>
                            <select wire:model="periodizationModel" id="periodizationModel"
                                    class="form-control @error('periodizationModel') is-invalid @enderror">
                                <option value="">— Seleziona —</option>
                                <option value="linear">Lineare</option>
                                <option value="undulating_dup">Ondulante (DUP)</option>
                                <option value="block">A blocchi</option>
                            </select>
                            @error('periodizationModel')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="startDate">Data inizio <span class="text-danger">*</span></label>
                            <input type="date" wire:model="startDate" id="startDate"
                                   class="form-control @error('startDate') is-invalid @enderror">
                            @error('startDate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button wire:click="prevStep" class="btn btn-secondary mr-2">
                        <i class="fas fa-arrow-left mr-1"></i> Indietro
                    </button>
                    <button wire:click="assign" class="btn btn-success" wire:loading.attr="disabled">
                        <span wire:loading.remove><i class="fas fa-check mr-1"></i> Conferma e assegna</span>
                        <span wire:loading><i class="fas fa-spinner fa-spin mr-1"></i> Attendere...</span>
                    </button>
                </div>
            @endif

        </div>
    </div>
</div>
