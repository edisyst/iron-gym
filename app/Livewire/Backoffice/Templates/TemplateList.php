<?php

namespace App\Livewire\Backoffice\Templates;

use App\Models\WorkoutTemplate;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class TemplateList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $goal = '';

    public string $active = '';

    /** @var array<string, array<string, string>> */
    protected $queryString = [
        'search' => ['except' => ''],
        'goal' => ['except' => ''],
        'active' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingGoal(): void
    {
        $this->resetPage();
    }

    public function updatingActive(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = WorkoutTemplate::with('creator')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->goal, fn ($q) => $q->where('goal', $this->goal))
            ->when($this->active === '1', fn ($q) => $q->where('is_active', true))
            ->when($this->active === '0', fn ($q) => $q->where('is_active', false))
            ->orderByDesc('created_at');

        return view('livewire.backoffice.templates.template-list', [
            'templates' => $query->paginate(15),
        ])->layout('layouts.backoffice')
            ->layoutData(['page_title' => 'Template di scheda']);
    }
}
