<?php

namespace App\Livewire\Backoffice\Admin;

use App\Models\DumbbellInventory;
use App\Models\PlateInventory;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Inventario Attrezzatura')]
class PlateInventoryManager extends Component
{
    use WithPagination;

    /**
     * @var array<int, array{quantity_pairs: int, color: string, is_active: bool}>
     */
    public array $editing = [];

    /**
     * @var array<int, array{quantity_pairs: int, color: string, is_active: bool}>
     */
    public array $editingDumbbells = [];

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

    public function startEditDumbbell(int $id): void
    {
        $dumbbell = DumbbellInventory::findOrFail($id);

        $this->editingDumbbells[$id] = [
            'quantity_pairs' => $dumbbell->quantity_pairs,
            'color' => $dumbbell->color ?? '',
            'is_active' => $dumbbell->is_active,
        ];
    }

    public function saveEditDumbbell(int $id): void
    {
        $this->validate([
            "editingDumbbells.{$id}.quantity_pairs" => 'required|integer|min:0|max:99',
            "editingDumbbells.{$id}.color" => 'nullable|string|max:32',
        ]);

        $dumbbell = DumbbellInventory::findOrFail($id);
        $dumbbell->update([
            'quantity_pairs' => (int) $this->editingDumbbells[$id]['quantity_pairs'],
            'color' => $this->editingDumbbells[$id]['color'] ?: null,
            'is_active' => (bool) $this->editingDumbbells[$id]['is_active'],
        ]);

        unset($this->editingDumbbells[$id]);
        session()->flash('success', 'Manubrio aggiornato.');
    }

    public function cancelEditDumbbell(int $id): void
    {
        unset($this->editingDumbbells[$id]);
    }

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('gestore'), 403);
    }

    public function render(): View
    {
        $plates = PlateInventory::orderByDesc('weight_kg')->paginate(20, pageName: 'platesPage');
        $dumbbells = DumbbellInventory::orderBy('weight_kg')->paginate(30, pageName: 'dumbbellsPage');

        return view('livewire.backoffice.admin.plate-inventory-manager', compact('plates', 'dumbbells'))
            ->layout('layouts.backoffice');
    }
}
