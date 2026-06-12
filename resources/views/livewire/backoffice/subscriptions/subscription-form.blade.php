<div>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Nuovo abbonamento</h3>
        </div>
        <form wire:submit="save">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tesserato <span class="text-danger">*</span></label>
                            <select wire:model.live="member_id" class="form-control @error('member_id') is-invalid @enderror">
                                <option value="">— Seleziona —</option>
                                @foreach ($members as $member)
                                    <option value="{{ $member->id }}">{{ $member->last_name }} {{ $member->first_name }}</option>
                                @endforeach
                            </select>
                            @error('member_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Piano <span class="text-danger">*</span></label>
                            <select wire:model.live="plan_id" class="form-control @error('plan_id') is-invalid @enderror">
                                <option value="">— Seleziona —</option>
                                @foreach ($plans as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->name }} ({{ $plan->price_formatted }})</option>
                                @endforeach
                            </select>
                            @error('plan_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Data inizio <span class="text-danger">*</span></label>
                            <input type="date" wire:model.live="started_at" class="form-control @error('started_at') is-invalid @enderror">
                            @error('started_at') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Scadenza (calcolata)</label>
                            <input type="date" value="{{ $expires_at }}" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Accessi inclusi</label>
                            <input type="text" value="{{ $accesses_remaining ?? '∞' }}" class="form-control" readonly>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Note</label>
                    <textarea wire:model="notes" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading wire:target="save" class="spinner-border spinner-border-sm mr-1"></span>
                    Salva
                </button>
                <a href="{{ route('backoffice.subscriptions.index') }}" class="btn btn-default ml-2">Annulla</a>
            </div>
        </form>
    </div>
</div>
