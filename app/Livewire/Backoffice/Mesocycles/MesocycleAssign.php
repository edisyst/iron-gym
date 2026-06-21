<?php

namespace App\Livewire\Backoffice\Mesocycles;

use App\Models\User;
use App\Models\WorkoutTemplate;
use App\Services\MesocycleInstantiationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Assegna mesociclo')]
class MesocycleAssign extends Component
{
    public int $step = 1;

    // Step 1
    public string $athleteId = '';

    public string $templateId = '';

    public string $goalFilter = '';

    // Step 2
    public string $name = '';

    public string $goal = '';

    public string $periodizationModel = '';

    public string $startDate = '';

    public int $weeksCount = 5;

    public function mount(): void
    {
        $this->startDate = now()->format('Y-m-d');
    }

    /**
     * Quando cambia il templateId: precompila nome, goal, weeks_count dal template
     */
    public function updatedTemplateId(string $value): void
    {
        if ($value === '') {
            $this->name = '';
            $this->goal = '';
            $this->weeksCount = 5;
            $this->periodizationModel = '';

            return;
        }

        $template = WorkoutTemplate::find($value);
        if ($template === null) {
            return;
        }

        $athlete = $this->athleteId !== '' ? User::find($this->athleteId) : null;

        // Compone il nome di default: "{template} — {atleta}"
        $this->name = $template->name.($athlete ? ' — '.$athlete->name : '');
        $this->goal = $template->goal;
        $this->weeksCount = $template->weeks_count;
        $this->periodizationModel = $template->periodization_model;
    }

    /**
     * Quando cambia l'atleta: aggiorna il nome di default se template già scelto
     */
    public function updatedAthleteId(string $value): void
    {
        if ($this->templateId === '' || $value === '') {
            return;
        }

        $template = WorkoutTemplate::find($this->templateId);
        $athlete = User::find($value);

        if ($template && $athlete) {
            $this->name = $template->name.' — '.$athlete->name;
        }
    }

    /**
     * Avanza dallo step 1 allo step 2 dopo validazione
     */
    public function nextStep(): void
    {
        $this->validate([
            'athleteId' => 'required|exists:users,id',
            'templateId' => 'required|exists:workout_templates,id',
        ], [
            'athleteId.required' => 'Seleziona un atleta.',
            'athleteId.exists' => 'Atleta non valido.',
            'templateId.required' => 'Seleziona un template.',
            'templateId.exists' => 'Template non valido.',
        ]);

        $this->step = 2;
    }

    /**
     * Torna allo step 1
     */
    public function prevStep(): void
    {
        $this->step = 1;
    }

    /**
     * Valida step 2, istanzia il mesociclo e redirect
     */
    public function assign(MesocycleInstantiationService $service): void
    {
        $this->validate([
            'name' => 'required|max:255',
            'goal' => 'required|in:hypertrophy,strength,cut,recomp,peaking,general',
            'periodizationModel' => 'required|in:linear,undulating_dup,block',
            'startDate' => 'required|date',
            'weeksCount' => 'required|integer|between:4,6',
        ], [
            'name.required' => 'Inserisci un nome per il mesociclo.',
            'goal.required' => 'Seleziona un obiettivo.',
            'periodizationModel.required' => 'Seleziona un modello di periodizzazione.',
            'startDate.required' => 'Seleziona la data di inizio.',
            'weeksCount.between' => 'Le settimane devono essere tra 4 e 6.',
        ]);

        $template = WorkoutTemplate::findOrFail($this->templateId);

        $service->instantiate($template, (int) $this->athleteId, auth()->id(), [
            'name' => $this->name,
            'goal' => $this->goal,
            'periodization_model' => $this->periodizationModel,
            'start_date' => $this->startDate,
            'weeks_count' => $this->weeksCount,
        ]);

        session()->flash('success', 'Mesociclo assegnato con successo.');

        $this->redirect(route('backoffice.mesocycles.index'));
    }

    /**
     * Template filtrati per goal (calcolato al render)
     *
     * @return Collection<int, WorkoutTemplate>
     */
    #[Computed]
    public function templates(): Collection
    {
        return WorkoutTemplate::where('is_active', true)
            ->with('creator')
            ->when($this->goalFilter !== '', fn ($q) => $q->where('goal', $this->goalFilter))
            ->orderBy('name')
            ->get();
    }

    /**
     * Template selezionato per la preview
     */
    #[Computed]
    public function selectedTemplate(): ?WorkoutTemplate
    {
        if ($this->templateId === '') {
            return null;
        }

        return WorkoutTemplate::with([
            'templateSessions' => fn ($q) => $q->orderBy('week_number')->orderBy('order_in_week'),
        ])->find($this->templateId);
    }

    public function render(): View
    {
        $athletes = User::role('atleta')->orderBy('name')->get();

        return view('livewire.backoffice.mesocycles.mesocycle-assign', [
            'athletes' => $athletes,
        ])->layout('layouts.backoffice', ['page_title' => 'Assegna mesociclo']);
    }
}
