<?php

namespace App\Livewire\Backoffice\Templates;

use App\Models\WorkoutTemplate;
use Illuminate\View\View;
use Livewire\Component;

class TemplateForm extends Component
{
    public string $name = '';

    public string $description = '';

    public string $goal = 'hypertrophy';

    public string $periodizationModel = 'linear';

    public int $weeksCount = 5;

    public int $daysPerWeek = 4;

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'goal' => ['required', 'in:hypertrophy,strength,cut,recomp,peaking,general'],
            'periodizationModel' => ['required', 'in:linear,undulating_dup,block'],
            'weeksCount' => ['required', 'integer', 'min:4', 'max:6'],
            'daysPerWeek' => ['required', 'integer', 'min:2', 'max:6'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        $template = WorkoutTemplate::create([
            'name' => $this->name,
            'description' => $this->description ?: null,
            'goal' => $this->goal,
            'periodization_model' => $this->periodizationModel,
            'weeks_count' => $this->weeksCount,
            'days_per_week' => $this->daysPerWeek,
            'created_by' => auth()->id(),
            'is_active' => true,
        ]);

        $this->redirect(route('backoffice.templates.builder', $template), navigate: false);
    }

    public function render(): View
    {
        return view('livewire.backoffice.templates.template-form')
            ->layout('layouts.backoffice')
            ->layoutData(['page_title' => 'Nuovo template']);
    }
}
