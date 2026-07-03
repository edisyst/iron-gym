<div>
    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-dumbbell mr-2"></i>
                Inventario Dischi
            </h3>
            <div class="card-tools">
                <small class="text-muted">Modifica coppie disponibili, colore e stato attivo per ogni disco.</small>
            </div>
        </div>

        <div class="card-body p-0">
            <table class="table table-hover table-sm mb-0">
                <thead>
                    <tr>
                        <th style="width:100px;">Peso (kg)</th>
                        <th style="width:120px;">Coppie</th>
                        <th style="width:120px;">Colore</th>
                        <th style="width:80px;">Attivo</th>
                        <th style="width:140px;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plates as $plate)
                        <tr>
                            {{-- Il peso kg non e' modificabile: e' il valore fisico del disco --}}
                            <td class="font-weight-bold">
                                {{ number_format((float) $plate->weight_kg, 2) }} kg
                            </td>

                            @if (isset($editing[$plate->id]))
                                {{-- Modalita' edit inline --}}
                                <td>
                                    <input type="number"
                                           wire:model="editing.{{ $plate->id }}.quantity_pairs"
                                           class="form-control form-control-sm @error("editing.{$plate->id}.quantity_pairs") is-invalid @enderror"
                                           min="0" max="99" style="width:80px;">
                                    @error("editing.{$plate->id}.quantity_pairs")
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </td>
                                <td>
                                    <input type="text"
                                           wire:model="editing.{{ $plate->id }}.color"
                                           class="form-control form-control-sm @error("editing.{$plate->id}.color") is-invalid @enderror"
                                           maxlength="32" placeholder="es. rosso">
                                    @error("editing.{$plate->id}.color")
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox"
                                               wire:model="editing.{{ $plate->id }}.is_active"
                                               class="custom-control-input"
                                               id="is_active_{{ $plate->id }}">
                                        <label class="custom-control-label" for="is_active_{{ $plate->id }}"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <button wire:click="saveEdit({{ $plate->id }})"
                                                wire:loading.attr="disabled"
                                                class="btn btn-success btn-sm">
                                            <i class="fas fa-check"></i> Salva
                                        </button>
                                        <button wire:click="cancelEdit({{ $plate->id }})"
                                                class="btn btn-secondary btn-sm">
                                            Annulla
                                        </button>
                                    </div>
                                </td>
                            @else
                                {{-- Modalita' visualizzazione --}}
                                <td>{{ $plate->quantity_pairs }}</td>
                                <td>
                                    @if ($plate->color)
                                        <span class="badge badge-secondary">{{ $plate->color }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($plate->is_active)
                                        <span class="badge badge-success">SI</span>
                                    @else
                                        <span class="badge badge-secondary">NO</span>
                                    @endif
                                </td>
                                <td>
                                    <button wire:click="startEdit({{ $plate->id }})"
                                            class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-pencil-alt"></i> Modifica
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Nessun disco in inventario. Esegui <code>php artisan db:seed --class=PlateInventorySeeder</code>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($plates->hasPages())
            <div class="card-footer">
                {{ $plates->links() }}
            </div>
        @endif
    </div>
</div>
