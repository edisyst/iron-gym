<?php

namespace App\Livewire\Backoffice\Admin;

use App\Models\PlateInventory;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Inventario Dischi')]
class PlateInventoryManager extends Component
{
    use WithPagination;

    /**
     * Mappa degli elementi in modalità edit: [id => ['quantity_pairs', 'color', 'is_active']]
     *
     * @var array<int, array{quantity_pairs: int, color: string, is_active: bool}>
     */
    public array $editing = [];

    public function startEdit(int $id): void
    {
        $plate = PlateInventory::findOrFail($id);

        $this->editing[$id] = [
            'quantity_pairs' => $plate->quantity_pairs,
            'color' => $plate->color ?? '',
            'is_active' => $plate->is_active,
        ];
    }

    public function saveEdit(int $id): void
    {
        $this->validate([
            "editing.{$id}.quantity_pairs" => 'required|integer|min:0|max:99',
            "editing.{$id}.color" => 'nullable|string|max:32',
        ]);

        $plate = PlateInventory::findOrFail($id);
        $plate->update([
            'quantity_pairs' => (int) $this->editing[$id]['quantity_pairs'],
            'color' => $this->editing[$id]['color'] ?: null,
            'is_active' => (bool) $this->editing[$id]['is_active'],
        ]);

        unset($this->editing[$id]);
        session()->flash('success', 'Disco aggiornato.');
    }

    public function cancelEdit(int $id): void
    {
        unset($this->editing[$id]);
    }

    public function render(): View
    {
        $plates = PlateInventory::orderByDesc('weight_kg')->paginate(20);

        return view('livewire.backoffice.admin.plate-inventory-manager', compact('plates'))
            ->layout('layouts.backoffice');
    }
}
