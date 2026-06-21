<div>
    @if ($sent)
        <div class="alert alert-success">
            <i class="fas fa-check-circle mr-2"></i>
            Campagna inviata in coda. Verrà elaborata dal worker Redis.
        </div>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-bullhorn mr-2"></i>Nuova campagna</h3>
        </div>
        <div class="card-body">
            <div class="row">

                {{-- Filtro destinatari --}}
                <div class="col-md-4">
                    <div class="card card-secondary card-outline">
                        <div class="card-header"><h3 class="card-title">Destinatari</h3></div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Filtro</label>
                                <select wire:model.live="filter" class="form-control">
                                    <option value="all">Tutti i tesserati</option>
                                    <option value="active">Abbonamento attivo</option>
                                    <option value="expired">Abbonamento scaduto</option>
                                    <option value="cert_expired">Certificato medico scaduto</option>
                                </select>
                            </div>
                            <div class="alert alert-info mb-0 py-2">
                                <i class="fas fa-users mr-1"></i>
                                <strong>{{ $this->recipientsCount }}</strong> destinatari trovati
                            </div>
                            @error('filter')
                                <span class="text-danger text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Messaggio --}}
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Template (opzionale)</label>
                        <select wire:model.live="templateId" class="form-control">
                            <option value="">— Testo libero —</option>
                            @foreach ($templates as $tpl)
                                <option value="{{ $tpl->id }}">{{ $tpl->name }} ({{ $tpl->channel }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Canale</label>
                        <select wire:model="channel" class="form-control">
                            <option value="email">Email</option>
                            <option value="sms">SMS</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Oggetto (solo email)</label>
                        <input
                            wire:model="subject"
                            type="text"
                            class="form-control @error('subject') is-invalid @enderror"
                            placeholder="Oggetto email..."
                        >
                        @error('subject')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label>Corpo del messaggio</label>
                        <small class="text-muted d-block mb-1">
                            Variabili disponibili: <code>@{{nome}}</code> <code>@{{cognome}}</code>
                            <code>@{{scadenza_abbonamento}}</code> <code>@{{scadenza_certificato}}</code>
                        </small>
                        <textarea
                            wire:model="body"
                            class="form-control @error('body') is-invalid @enderror"
                            rows="6"
                            placeholder="Ciao @{{nome}}, ..."
                        ></textarea>
                        @error('body')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <button
                        wire:click="send"
                        wire:confirm="Inviare la campagna a {{ $this->recipientsCount }} destinatari?"
                        class="btn btn-primary"
                        @if ($this->recipientsCount === 0) disabled @endif
                    >
                        <i class="fas fa-paper-plane mr-1"></i>
                        Invia campagna
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
