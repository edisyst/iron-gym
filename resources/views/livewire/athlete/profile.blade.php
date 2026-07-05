<div>
    <h2 style="font-size:20px;font-weight:700;margin-bottom:4px;">Profilo</h2>
    <p style="font-size:13px;color:var(--ig-text-3);margin-bottom:20px;">{{ auth()->user()->email }}</p>

    {{-- Tab sezioni --}}
    <div class="ig-tab-group">
        <button type="button" wire:click="$set('activeSection','info')"
                class="ig-tab {{ $activeSection === 'info' ? 'ig-tab--active' : '' }}">
            Dati
        </button>
        <button type="button" wire:click="$set('activeSection','password')"
                class="ig-tab {{ $activeSection === 'password' ? 'ig-tab--active' : '' }}">
            Password
        </button>
        <button type="button" wire:click="$set('activeSection','danger')"
                class="ig-tab ig-tab--danger {{ $activeSection === 'danger' ? 'ig-tab--active' : '' }}">
            Account
        </button>
    </div>

    {{-- ===== SEZIONE DATI ===== --}}
    @if ($activeSection === 'info')
        <form wire:submit="updateProfile">
            <div class="athlete-card">
                <div class="section-title" style="margin-bottom:16px;">INFORMAZIONI PERSONALI</div>

                @if ($profileMessage)
                    <div style="background:var(--ig-success-subtle);border:1px solid rgba(34,197,94,.3);border-radius:8px;
                                padding:10px 14px;margin-bottom:16px;font-size:14px;color:var(--ig-success);">
                        {{ $profileMessage }}
                    </div>
                @endif

                <div style="margin-bottom:16px;">
                    <label class="ig-form-label">Nome</label>
                    <input type="text" wire:model="name"
                           class="ig-form-input {{ $errors->has('name') ? 'is-invalid' : '' }}">
                    @error('name') <span class="ig-field-error">{{ $message }}</span> @enderror
                </div>

                <div style="margin-bottom:20px;">
                    <label class="ig-form-label">Email</label>
                    <input type="email" wire:model="email"
                           class="ig-form-input {{ $errors->has('email') ? 'is-invalid' : '' }}">
                    @error('email') <span class="ig-field-error">{{ $message }}</span> @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled"
                        style="width:100%;background:var(--ig-accent);color:#fff;border:none;border-radius:8px;
                               padding:13px;font-size:15px;font-weight:600;cursor:pointer;">
                    <span wire:loading.remove wire:target="updateProfile">Salva modifiche</span>
                    <span wire:loading wire:target="updateProfile">Salvataggio...</span>
                </button>
            </div>
        </form>

        {{-- Info ruolo --}}
        <div class="athlete-card">
            <div class="section-title" style="margin-bottom:12px;">RUOLO</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                @foreach (auth()->user()->getRoleNames() as $role)
                    <span class="athlete-badge badge-gray" style="text-transform:capitalize;">{{ $role }}</span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ===== SEZIONE PASSWORD ===== --}}
    @if ($activeSection === 'password')
        <form wire:submit="updatePassword">
            <div class="athlete-card">
                <div class="section-title" style="margin-bottom:16px;">CAMBIA PASSWORD</div>

                @if ($passwordMessage)
                    <div style="background:var(--ig-success-subtle);border:1px solid rgba(34,197,94,.3);border-radius:8px;
                                padding:10px 14px;margin-bottom:16px;font-size:14px;color:var(--ig-success);">
                        {{ $passwordMessage }}
                    </div>
                @endif

                <div style="margin-bottom:16px;">
                    <label class="ig-form-label">Password attuale</label>
                    <input type="password" wire:model="currentPassword" autocomplete="current-password"
                           class="ig-form-input {{ $errors->has('currentPassword') ? 'is-invalid' : '' }}">
                    @error('currentPassword') <span class="ig-field-error">{{ $message }}</span> @enderror
                </div>

                <div style="margin-bottom:16px;">
                    <label class="ig-form-label">Nuova password</label>
                    <input type="password" wire:model="newPassword" autocomplete="new-password"
                           class="ig-form-input {{ $errors->has('newPassword') ? 'is-invalid' : '' }}">
                    @error('newPassword') <span class="ig-field-error">{{ $message }}</span> @enderror
                </div>

                <div style="margin-bottom:20px;">
                    <label class="ig-form-label">Conferma nuova password</label>
                    <input type="password" wire:model="newPasswordConfirmation" autocomplete="new-password"
                           class="ig-form-input {{ $errors->has('newPasswordConfirmation') ? 'is-invalid' : '' }}">
                    @error('newPasswordConfirmation') <span class="ig-field-error">{{ $message }}</span> @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled"
                        style="width:100%;background:var(--ig-accent);color:#fff;border:none;border-radius:8px;
                               padding:13px;font-size:15px;font-weight:600;cursor:pointer;">
                    <span wire:loading.remove wire:target="updatePassword">Aggiorna password</span>
                    <span wire:loading wire:target="updatePassword">Aggiornamento...</span>
                </button>
            </div>
        </form>
    @endif

    {{-- ===== SEZIONE ACCOUNT / DANGER ===== --}}
    @if ($activeSection === 'danger')
        <div class="athlete-card" style="border:1px solid #2a1a1a;">
            <div class="section-title" style="margin-bottom:8px;color:var(--ig-danger);">ELIMINA ACCOUNT</div>
            <p style="font-size:13px;color:var(--ig-text-2);margin-bottom:16px;line-height:1.5;">
                Una volta eliminato, tutti i tuoi dati saranno rimossi definitivamente. Questa operazione non è reversibile.
            </p>

            <form wire:submit="deleteAccount"
                  x-data="{ confirm: false }"
                  x-on:submit.prevent="if(confirm) $wire.deleteAccount(); else confirm = true">

                <div x-show="confirm" style="margin-bottom:16px;">
                    <label class="ig-form-label">Conferma con la tua password</label>
                    <input type="password" wire:model="currentPassword" autocomplete="current-password"
                           placeholder="La tua password attuale"
                           class="ig-form-input is-invalid">
                    @error('currentPassword') <span class="ig-field-error">{{ $message }}</span> @enderror
                </div>

                <button type="submit"
                        style="width:100%;background:var(--ig-danger);color:#fff;border:none;border-radius:8px;
                               padding:13px;font-size:15px;font-weight:600;cursor:pointer;">
                    <span x-text="confirm ? 'Conferma eliminazione account' : 'Elimina il mio account'"></span>
                </button>

                <button type="button" x-show="confirm" @click="confirm = false"
                        style="width:100%;margin-top:8px;background:transparent;color:var(--ig-text-2);
                               border:1px solid var(--ig-border);border-radius:8px;padding:11px;font-size:14px;cursor:pointer;">
                    Annulla
                </button>
            </form>
        </div>
    @endif
</div>
