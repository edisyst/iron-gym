<?php

namespace App\Livewire\Backoffice\Mesocycles;

use App\Models\Mesocycle;
use App\Models\User;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Mesocicli')]
class MesocycleList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $trainerFilter = '';

    public string $athleteFilter = '';

    /**
     * Resetta la paginazione ogni volta che cambiano i filtri
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTrainerFilter(): void
    {
        $this->resetPage();
    }

    public function updatingAthleteFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Label italiane per l'obiettivo del mesociclo
     */
    public function goalLabel(string $goal): string
    {
        return match ($goal) {
            'hypertrophy' => 'Ipertrofia',
            'strength' => 'Forza',
            'cut' => 'Definizione',
            'recomp' => 'Ricomposizione',
            'peaking' => 'Peaking',
            'general' => 'Generale',
            default => $goal,
        };
    }

    /**
     * Classe badge AdminLTE in base allo status
     */
    public function statusBadgeClass(string $status): string
    {
        return match ($status) {
            'draft' => 'badge-secondary',
            'active' => 'badge-success',
            'completed' => 'badge-primary',
            'aborted' => 'badge-danger',
            default => 'badge-secondary',
        };
    }

    /**
     * Label italiana per lo status
     */
    public function statusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Bozza',
            'active' => 'Attivo',
            'completed' => 'Completato',
            'aborted' => 'Interrotto',
            default => $status,
        };
    }

    public function render(): View
    {
        $mesocycles = Mesocycle::with(['athlete', 'trainer', 'template'])
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%');
            })
            ->when($this->statusFilter, function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->when($this->trainerFilter, function ($q) {
                $q->where('trainer_id', $this->trainerFilter);
            })
            ->when($this->athleteFilter, function ($q) {
                $q->where('athlete_id', $this->athleteFilter);
            })
            ->orderByDesc('created_at')
            ->paginate(15);

        // N+1 evitato: eager loading già sopra.
        // Carica trainer e atleti per i select filtro
        $trainers = User::role('trainer')->orderBy('name')->get();
        $athletes = User::role('atleta')->orderBy('name')->get();

        return view('livewire.backoffice.mesocycles.mesocycle-list', [
            'mesocycles' => $mesocycles,
            'trainers' => $trainers,
            'athletes' => $athletes,
        ])->layout('layouts.backoffice', ['page_title' => 'Mesocicli']);
    }
}
