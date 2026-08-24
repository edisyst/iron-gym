<?php

namespace App\Livewire\Backoffice\Search;

use App\Models\Member;
use App\Models\Mesocycle;
use App\Models\User;
use App\Models\WorkoutTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

class GlobalSearch extends Component
{
    #[Url(as: 'q')]
    public string $query = '';

    public function mount(): void
    {
        if ($this->query === '') {
            $this->query = request()->string('q')->toString();
        }
    }

    public function render(): View
    {
        $athletes = collect();
        $trainers = collect();
        $templates = collect();
        $mesocycles = collect();

        if (mb_strlen(trim($this->query)) >= 2) {
            $term = '%'.trim($this->query).'%';

            $athletes = Member::where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhereRaw(
                        DB::connection()->getDriverName() === 'sqlite'
                            ? "(first_name || ' ' || last_name) LIKE ?"
                            : "CONCAT(first_name, ' ', last_name) LIKE ?",
                        [$term]
                    );
            })
                ->whereHas('user', fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', 'atleta')))
                ->with('user')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->limit(20)
                ->get();

            $trainers = User::role('trainer')
                ->where('name', 'like', $term)
                ->orderBy('name')
                ->limit(20)
                ->get();

            $templates = WorkoutTemplate::where('name', 'like', $term)
                ->orderBy('name')
                ->limit(20)
                ->get();

            $mesocycles = Mesocycle::where('name', 'like', $term)
                ->with('athlete:id,name')
                ->orderBy('name')
                ->limit(20)
                ->get();
        }

        return view('livewire.backoffice.search.global-search', [
            'athletes' => $athletes,
            'trainers' => $trainers,
            'templates' => $templates,
            'mesocycles' => $mesocycles,
        ])->layout('layouts.backoffice', ['page_title' => 'Ricerca']);
    }
}
