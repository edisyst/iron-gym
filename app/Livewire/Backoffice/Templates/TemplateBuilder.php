<?php

namespace App\Livewire\Backoffice\Templates;

use App\Models\Exercise;
use App\Models\TemplateSession;
use App\Models\TemplateSessionExercise;
use App\Models\WorkoutTemplate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class TemplateBuilder extends Component
{
    public WorkoutTemplate $template;

    public int $activeWeek = 1;

    public string $exerciseSearch = '';

    public function mount(WorkoutTemplate $template): void
    {
        $this->template = $template;
    }

    /** Aggiunge una nuova sessione alla settimana attiva */
    public function addSession(): void
    {
        $count = TemplateSession::where('template_id', $this->template->id)
            ->where('week_number', $this->activeWeek)
            ->count();

        TemplateSession::create([
            'template_id' => $this->template->id,
            'week_number' => $this->activeWeek,
            'name' => 'Sessione '.($count + 1),
            'order_in_week' => $count + 1,
        ]);
    }

    /** Rimuove una sessione con tutti i suoi esercizi (hard delete con cascade) */
    public function removeSession(int $sessionId): void
    {
        $session = TemplateSession::findOrFail($sessionId);
        // Hard delete: la FK con cascade elimina gli exercise figli
        $session->delete();
    }

    /** Aggiorna il nome di una sessione */
    public function updateSessionName(int $sessionId, string $name): void
    {
        TemplateSession::where('id', $sessionId)->update(['name' => trim($name) ?: 'Sessione']);
    }

    /** Aggiunge un esercizio a una sessione tramite ID (dalla ricerca sidebar) */
    public function addExerciseById(int $sessionId, int $exerciseId): void
    {
        $maxOrder = TemplateSessionExercise::where('template_session_id', $sessionId)->max('order_in_session') ?? 0;

        TemplateSessionExercise::create([
            'template_session_id' => $sessionId,
            'exercise_id' => $exerciseId,
            'order_in_session' => $maxOrder + 1,
            'technique_type' => 'straight',
            'planned_sets_count' => 3,
            'planned_reps' => 10,
            'planned_rir' => 2,
            'planned_rest_sec' => 90,
        ]);

        // Reset ricerca dopo aggiunta
        $this->exerciseSearch = '';
    }

    /** Rimuove un esercizio dal template */
    public function removeExercise(int $exerciseId): void
    {
        $tse = TemplateSessionExercise::findOrFail($exerciseId);
        $groupKey = $tse->group_key;

        $tse->delete();

        // Se era in un gruppo con solo un elemento rimasto, rimuovi il group_key dall'altro
        if ($groupKey !== null) {
            $remaining = TemplateSessionExercise::where('group_key', $groupKey)->get();
            if ($remaining->count() === 1) {
                $remaining->first()->update(['group_key' => null, 'group_type' => null]);
            }
        }
    }

    /**
     * Aggiorna un singolo campo di un TemplateSessionExercise.
     * Whitelist campi per sicurezza.
     */
    public function updateExerciseField(int $exerciseId, string $field, mixed $value): void
    {
        $allowed = ['technique_type', 'tempo', 'planned_sets_count', 'planned_reps', 'planned_rir', 'planned_rest_sec', 'note'];

        if (! in_array($field, $allowed, strict: true)) {
            return;
        }

        TemplateSessionExercise::where('id', $exerciseId)->update([$field => $value ?: null]);
    }

    /** Riordina gli esercizi di una sessione dopo drag&drop SortableJS */
    /**
     * @param  array<int>  $orderedIds
     */
    #[On('exercises-reordered')]
    public function reorderExercises(int $sessionId, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            TemplateSessionExercise::where('id', $id)
                ->where('template_session_id', $sessionId)
                ->update(['order_in_session' => $index + 1]);
        }
    }

    /**
     * Raggruppa l'esercizio con quello successivo (per order) nella stessa sessione.
     * Se $grouped=false, rimuove il group_key dall'esercizio.
     */
    public function toggleGroup(int $exerciseId, bool $grouped): void
    {
        $tse = TemplateSessionExercise::findOrFail($exerciseId);

        if ($grouped) {
            if ($tse->group_key !== null) {
                return; // già in un gruppo
            }

            // Trova il successivo per order nella stessa sessione
            $next = TemplateSessionExercise::where('template_session_id', $tse->template_session_id)
                ->where('order_in_session', '>', $tse->order_in_session)
                ->orderBy('order_in_session')
                ->first();

            $groupKey = Str::uuid()->toString();

            $tse->update(['group_key' => $groupKey, 'group_type' => 'superset']);

            if ($next !== null) {
                $next->update(['group_key' => $groupKey, 'group_type' => 'superset']);
            }
        } else {
            $groupKey = $tse->group_key;
            $tse->update(['group_key' => null, 'group_type' => null]);

            // Se nel gruppo rimane un solo esercizio, togli anche a lui il group_key
            if ($groupKey !== null) {
                $remaining = TemplateSessionExercise::where('group_key', $groupKey)->get();
                if ($remaining->count() === 1) {
                    $remaining->first()->update(['group_key' => null, 'group_type' => null]);
                }
            }
        }
    }

    /** Aggiorna il group_type a tutti gli esercizi con lo stesso group_key */
    public function updateGroupType(int $exerciseId, string $groupType): void
    {
        $allowed = ['superset', 'giant_set', 'circuit'];
        if (! in_array($groupType, $allowed, strict: true)) {
            return;
        }

        $tse = TemplateSessionExercise::findOrFail($exerciseId);

        if ($tse->group_key !== null) {
            TemplateSessionExercise::where('group_key', $tse->group_key)
                ->update(['group_type' => $groupType]);
        }
    }

    /**
     * Risultati ricerca esercizi per la sidebar.
     * Ritorna Collection vuota se la query è < 2 caratteri.
     *
     * @return Collection<int, Exercise>
     */
    #[Computed]
    public function exerciseSearchResults(): Collection
    {
        if (strlen($this->exerciseSearch) < 2) {
            return Exercise::newModelInstance()->newCollection();
        }

        return Exercise::with('muscles')
            ->where('name_it', 'like', "%{$this->exerciseSearch}%")
            ->limit(20)
            ->get();
    }

    public function render(): View
    {
        $sessions = TemplateSession::where('template_id', $this->template->id)
            ->where('week_number', $this->activeWeek)
            ->with([
                'templateExercises' => fn ($q) => $q->orderBy('order_in_session'),
                'templateExercises.exercise.muscles',
            ])
            ->orderBy('order_in_week')
            ->get();

        return view('livewire.backoffice.templates.template-builder', [
            'sessions' => $sessions,
            'exerciseSearchResults' => $this->exerciseSearchResults(),
        ])->layout('layouts.backoffice')
            ->layoutData(['page_title' => 'Builder: '.$this->template->name]);
    }
}
