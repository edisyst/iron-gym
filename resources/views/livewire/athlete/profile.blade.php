<div>
    <h2 style="font-size:20px;font-weight:700;margin-bottom:4px;">Profilo</h2>
    <p style="font-size:13px;color:#666;margin-bottom:20px;">{{ auth()->user()->email }}</p>

    {{-- Tab sezioni --}}
    <div style="display:flex;gap:0;margin-bottom:20px;background:#1E1E1E;border-radius:10px;padding:4px;">
        <button type="button"
                wire:click="$set('activeSection','info')"
                style="flex:1;border:none;border-radius:8px;padding:9px;font-size:13px;font-weight:600;cursor:pointer;transition:all 0.15s;
                       {{ $activeSection === 'info' ? 'background:#FF6B00;color:#fff;' : 'background:transparent;color:#888;' }}">
            Dati
        </button>
        <button type="button"
                wire:click="$set('activeSection','password')"
                style="flex:1;border:none;border-radius:8px;padding:9px;font-size:13px;font-weight:600;cursor:pointer;transition:all 0.15s;
                       {{ $activeSection === 'password' ? 'background:#FF6B00;color:#fff;' : 'background:transparent;color:#888;' }}">
            Password
        </button>
        <button type="button"
                wire:click="$set('activeSection','danger')"
                style="flex:1;border:none;border-radius:8px;padding:9px;font-size:13px;font-weight:600;cursor:pointer;transition:all 0.15s;
                       {{ $activeSection === 'danger' ? 'background:#ef4444;color:#fff;' : 'background:transparent;color:#888;' }}">
            Account
        </button>
    </div>

    {{-- ===== SEZIONE DATI ===== --}}
    @if ($activeSection === 'info')
        <form wire:submit="updateProfile">
            <div class="athlete-card">
                <div class="section-title" style="margin-bottom:16px;">INFORMAZIONI PERSONALI</div>

                @if ($profileMessage)
                    <div style="background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.3);border-radius:8px;
                                padding:10px 14px;margin-bottom:16px;font-size:14px;color:#22c55e;">
                        {{ $profileMessage }}
                    </div>
                @endif

                <div style="margin-bottom:16px;">
                    <label style="display:block;font-size:12px;color:#666;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">
                        Nome
                    </label>
                    <input type="text"
                           wire:model="name"
                           style="width:100%;background:#2A2A2A;border:1px solid {{ $errors->has('name') ? '#ef4444' : '#333' }};
                                  border-radius:8px;color:#fff;padding:12px 14px;font-size:15px;">
                    @error('name')
                        <p style="font-size:12px;color:#ef4444;margin-top:4px;">{{ $message }}</p>
                    @enderror
                </div>

                <div style="margin-bottom:20px;">
                    <label style="display:block;font-size:12px;color:#666;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">
                        Email
                    </label>
                    <input type="email"
                           wire:model="email"
                           style="width:100%;background:#2A2A2A;border:1px solid {{ $errors->has('email') ? '#ef4444' : '#333' }};
                                  border-radius:8px;color:#fff;padding:12px 14px;font-size:15px;">
                    @error('email')
                        <p style="font-size:12px;color:#ef4444;margin-top:4px;">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        wire:loading.attr="disabled"
                        style="width:100%;background:#FF6B00;color:#fff;border:none;border-radius:8px;
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
                    <div style="background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.3);border-radius:8px;
                                padding:10px 14px;margin-bottom:16px;font-size:14px;color:#22c55e;">
                        {{ $passwordMessage }}
                    </div>
                @endif

                <div style="margin-bottom:16px;">
                    <label style="display:block;font-size:12px;color:#666;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">
                        Password attuale
                    </label>
                    <input type="password"
                           wire:model="currentPassword"
                           autocomplete="current-password"
                           style="width:100%;background:#2A2A2A;border:1px solid {{ $errors->has('currentPassword') ? '#ef4444' : '#333' }};
                                  border-radius:8px;color:#fff;padding:12px 14px;font-size:15px;">
                    @error('currentPassword')
                        <p style="font-size:12px;color:#ef4444;margin-top:4px;">{{ $message }}</p>
                    @enderror
                </div>

                <div style="margin-bottom:16px;">
                    <label style="display:block;font-size:12px;color:#666;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">
                        Nuova password
                    </label>
                    <input type="password"
                           wire:model="newPassword"
                           autocomplete="new-password"
                           style="width:100%;background:#2A2A2A;border:1px solid {{ $errors->has('newPassword') ? '#ef4444' : '#333' }};
                                  border-radius:8px;color:#fff;padding:12px 14px;font-size:15px;">
                    @error('newPassword')
                        <p style="font-size:12px;color:#ef4444;margin-top:4px;">{{ $message }}</p>
                    @enderror
                </div>

                <div style="margin-bottom:20px;">
                    <label style="display:block;font-size:12px;color:#666;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">
                        Conferma nuova password
                    </label>
                    <input type="password"
                           wire:model="newPasswordConfirmation"
                           autocomplete="new-password"
                           style="width:100%;background:#2A2A2A;border:1px solid {{ $errors->has('newPasswordConfirmation') ? '#ef4444' : '#333' }};
                                  border-radius:8px;color:#fff;padding:12px 14px;font-size:15px;">
                    @error('newPasswordConfirmation')
                        <p style="font-size:12px;color:#ef4444;margin-top:4px;">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        wire:loading.attr="disabled"
                        style="width:100%;background:#FF6B00;color:#fff;border:none;border-radius:8px;
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
            <div class="section-title" style="margin-bottom:8px;color:#ef4444;">ELIMINA ACCOUNT</div>
            <p style="font-size:13px;color:#888;margin-bottom:16px;line-height:1.5;">
                Una volta eliminato, tutti i tuoi dati saranno rimossi definitivamente. Questa operazione non è reversibile.
            </p>

            <form wire:submit="deleteAccount"
                  x-data="{ confirm: false }"
                  x-on:submit.prevent="if(confirm) $wire.deleteAccount(); else confirm = true">

                <div x-show="confirm" style="margin-bottom:16px;">
                    <label style="display:block;font-size:12px;color:#666;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">
                        Conferma con la tua password
                    </label>
                    <input type="password"
                           wire:model="currentPassword"
                           autocomplete="current-password"
                           placeholder="La tua password attuale"
                           style="width:100%;background:#2A2A2A;border:1px solid #ef4444;
                                  border-radius:8px;color:#fff;padding:12px 14px;font-size:15px;">
                    @error('currentPassword')
                        <p style="font-size:12px;color:#ef4444;margin-top:4px;">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        style="width:100%;background:#ef4444;color:#fff;border:none;border-radius:8px;
                               padding:13px;font-size:15px;font-weight:600;cursor:pointer;">
                    <span x-text="confirm ? 'Conferma eliminazione account' : 'Elimina il mio account'"></span>
                </button>

                <button type="button" x-show="confirm" @click="confirm = false"
                        style="width:100%;margin-top:8px;background:transparent;color:#888;border:1px solid #333;
                               border-radius:8px;padding:11px;font-size:14px;cursor:pointer;">
                    Annulla
                </button>
            </form>
        </div>
    @endif
</div>
