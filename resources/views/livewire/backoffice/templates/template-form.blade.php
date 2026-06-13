<div>
    <div class="card" style="max-width: 680px">
        <div class="card-header">
            <h3 class="card-title">Nuovo template</h3>
        </div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form wire:submit="save">
                <div class="form-group">
                    <label>Nome <span class="text-danger">*</span></label>
                    <input type="text" wire:model="name"
                           class="form-control @error('name') is-invalid @enderror"
                           placeholder="Es. Massa invernale 5 giorni">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label>Descrizione</label>
                    <textarea wire:model="description" class="form-control" rows="3"
                              placeholder="Descrizione del programma..."></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Obiettivo <span class="text-danger">*</span></label>
                            <select wire:model="goal" class="form-control @error('goal') is-invalid @enderror">
                                <option value="hypertrophy">Ipertrofia</option>
                                <option value="strength">Forza</option>
                                <option value="cut">Definizione</option>
                                <option value="recomp">Recomposizione</option>
                                <option value="peaking">Peaking</option>
                                <option value="general">Generale</option>
                            </select>
                            @error('goal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Modello di periodizzazione <span class="text-danger">*</span></label>
                            <select wire:model="periodizationModel" class="form-control @error('periodizationModel') is-invalid @enderror">
                                <option value="linear">Lineare</option>
                                <option value="undulating_dup">Ondulante (DUP)</option>
                                <option value="block">A blocchi</option>
                            </select>
                            @error('periodizationModel') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Numero di settimane</label>
                            <select wire:model="weeksCount" class="form-control">
                                @for ($w = 4; $w <= 6; $w++)
                                    <option value="{{ $w }}">{{ $w }} settimane</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Giorni per settimana</label>
                            <select wire:model="daysPerWeek" class="form-control">
                                @for ($d = 2; $d <= 6; $d++)
                                    <option value="{{ $d }}">{{ $d }} giorni</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-tools"></i> Crea e apri builder
                    </button>
                    <a href="{{ route('backoffice.templates.index') }}" class="btn btn-default ml-2">
                        Annulla
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
