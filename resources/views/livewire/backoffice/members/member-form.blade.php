<div>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $memberId ? 'Modifica tesserato' : 'Nuovo tesserato' }}</h3>
        </div>
        <form wire:submit="save">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Cognome <span class="text-danger">*</span></label>
                            <input type="text" wire:model="last_name" class="form-control @error('last_name') is-invalid @enderror">
                            @error('last_name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nome <span class="text-danger">*</span></label>
                            <input type="text" wire:model="first_name" class="form-control @error('first_name') is-invalid @enderror">
                            @error('first_name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Email <span class="text-danger">*</span></label>
                            <input type="email" wire:model="email" class="form-control @error('email') is-invalid @enderror">
                            @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Telefono</label>
                            <input type="text" wire:model="phone" class="form-control @error('phone') is-invalid @enderror">
                            @error('phone') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Data di nascita</label>
                            <input type="date" wire:model="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror">
                            @error('date_of_birth') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Codice fiscale</label>
                            <input type="text" wire:model="fiscal_code" class="form-control @error('fiscal_code') is-invalid @enderror" maxlength="16">
                            @error('fiscal_code') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Scadenza cert. medico</label>
                            <input type="date" wire:model="medical_cert_expiry" class="form-control @error('medical_cert_expiry') is-invalid @enderror">
                            @error('medical_cert_expiry') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Indirizzo</label>
                            <input type="text" wire:model="address" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Città</label>
                            <input type="text" wire:model="city" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>CAP</label>
                            <input type="text" wire:model="postal_code" class="form-control" maxlength="10">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Note</label>
                    <textarea wire:model="notes" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-check">
                    <input type="checkbox" wire:model="is_active" class="form-check-input" id="is_active">
                    <label class="form-check-label" for="is_active">Tesserato attivo</label>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading wire:target="save" class="spinner-border spinner-border-sm mr-1"></span>
                    Salva
                </button>
                <a href="{{ route('backoffice.members.index') }}" class="btn btn-default ml-2">Annulla</a>
            </div>
        </form>
    </div>
</div>
