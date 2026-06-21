<?php

namespace App\Livewire\Backoffice\Templates;

use App\Models\TemplateSession;
use App\Models\TemplateSessionExercise;
use App\Models\WorkoutTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    public function duplicate(int $templateId): void
    {
        $source = WorkoutTemplate::with([
            'templateSessions.templateExercises',
        ])->findOrFail($templateId);

        $copy = DB::transaction(function () use ($source): WorkoutTemplate {
            $copy = WorkoutTemplate::create([
                'name' => 'Copia di '.$source->name,
                'description' => $source->description,
                'goal' => $source->goal,
                'periodization_model' => $source->periodization_model,
                'weeks_count' => $source->weeks_count,
                'days_per_week' => $source->days_per_week,
                'created_by' => auth()->id(),
                'is_active' => false,
            ]);

            foreach ($source->templateSessions as $session) {
                $newSession = TemplateSession::create([
                    'template_id' => $copy->id,
                    'week_number' => $session->week_number,
                    'name' => $session->name,
                    'order_in_week' => $session->order_in_week,
                ]);

                $groupKeyMap = [];
                foreach ($session->templateExercises as $ex) {
                    $newGroupKey = null;
                    if ($ex->group_key !== null) {
                        if (! isset($groupKeyMap[$ex->group_key])) {
                            $groupKeyMap[$ex->group_key] = Str::uuid()->toString();
                        }
                        $newGroupKey = $groupKeyMap[$ex->group_key];
                    }

                    TemplateSessionExercise::create([
                        'template_session_id' => $newSession->id,
                        'exercise_id' => $ex->exercise_id,
                        'order_in_session' => $ex->order_in_session,
                        'technique_type' => $ex->technique_type,
                        'tempo' => $ex->tempo,
                        'planned_sets_count' => $ex->planned_sets_count,
                        'planned_reps' => $ex->planned_reps,
                        'planned_rir' => $ex->planned_rir,
                        'planned_rest_sec' => $ex->planned_rest_sec,
                        'note' => $ex->note,
                        'group_key' => $newGroupKey,
                        'group_type' => $ex->group_type,
                    ]);
                }
            }

            return $copy;
        });

        $this->redirect(route('backoffice.templates.builder', $copy));
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
