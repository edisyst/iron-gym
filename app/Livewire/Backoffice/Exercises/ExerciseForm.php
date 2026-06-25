<?php

namespace App\Livewire\Backoffice\Exercises;

use App\Models\Equipment;
use App\Models\Exercise;
use App\Models\ExerciseMuscle;
use App\Models\MovementPattern;
use App\Models\Muscle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class ExerciseForm extends Component
{
    use WithFileUploads;

    public ?Exercise $exercise = null;

    // Campi base
    public string $slug = '';

    public string $nameIt = '';

    public string $description = '';

    public string $executionDescription = '';

    // Pattern motorio
    public string $patternType = 'compound_pattern';

    public ?int $compoundPatternId = null;

    public ?int $jointActionId = null;

    // Classificazione
    public string $mechanic = 'compound';

    public string $plane = 'sagittal';

    public string $laterality = 'bilateral';

    public string $skillLevel = 'intermediate';

    public string $measurementType = 'reps_weight';

    // URL media
    public string $videoUrl = '';

    public string $thumbnailUrl = '';

    // Upload immagine locale
    /** @var TemporaryUploadedFile|null */
    public $imageFile = null;

    // Attrezzatura selezionata (array di equipment_id)
    /** @var array<int> */
    public array $selectedEquipment = [];

    /**
     * Dati muscoli: keyed by muscle_id (stringa) => ['selected' => bool, 'role' => string, 'pct' => int]
     *
     * @var array<string, array{selected: bool, role: string, pct: int}>
     */
    public array $muscleData = [];

    /** ID esercizio in edit (per unique ignore) */
    public ?int $exerciseId = null;

    /** Slug generato automaticamente dal nome (per rilevare se è ancora "auto") */
    private string $autoSlug = '';

    public function mount(?Exercise $exercise = null): void
    {
        // Carica tutti i muscoli per popolare muscleData
        $muscles = Muscle::orderBy('muscle_group')->orderBy('display_order')->get();

        foreach ($muscles as $muscle) {
            $this->muscleData[(string) $muscle->id] = [
                'selected' => false,
                'role' => 'primary',
                'pct' => 100,
            ];
        }

        if ($exercise !== null && $exercise->exists) {
            $this->exercise = $exercise->load(['muscles', 'equipment']);
            $this->exerciseId = $exercise->id;

            $this->slug = $exercise->slug;
            $this->nameIt = $exercise->name_it;
            $this->description = $exercise->description ?? '';
            $this->executionDescription = $exercise->execution_description ?? '';
            $this->mechanic = $exercise->mechanic;
            $this->plane = $exercise->plane;
            $this->laterality = $exercise->laterality;
            $this->skillLevel = $exercise->skill_level;
            $this->measurementType = $exercise->measurement_type;
            $this->videoUrl = $exercise->video_url ?? '';
            $this->thumbnailUrl = $exercise->thumbnail_url ?? '';

            // Imposta pattern type e id
            if ($exercise->compound_pattern_id !== null) {
                $this->patternType = 'compound_pattern';
                $this->compoundPatternId = $exercise->compound_pattern_id;
            } else {
                $this->patternType = 'joint_action';
                $this->jointActionId = $exercise->joint_action_id;
            }

            // Popola attrezzatura selezionata
            $this->selectedEquipment = $exercise->equipment->pluck('id')->map(fn ($id) => (int) $id)->toArray();

            // Popola dati muscoli leggendo i pivot direttamente (type-safe)
            $pivots = ExerciseMuscle::where('exercise_id', $exercise->id)
                ->get()
                ->keyBy('muscle_id');

            foreach ($exercise->muscles as $muscle) {
                $pivotRow = $pivots->get($muscle->id);
                $this->muscleData[(string) $muscle->id] = [
                    'selected' => true,
                    'role' => $pivotRow instanceof ExerciseMuscle ? $pivotRow->role : 'primary',
                    'pct' => $pivotRow instanceof ExerciseMuscle ? (int) $pivotRow->contribution_pct : 100,
                ];
            }
        }

        $this->autoSlug = $this->slug;
    }

    /** Rigenera lo slug automaticamente se il campo è vuoto o ancora uguale all'auto-slug */
    public function updatedNameIt(string $value): void
    {
        $generated = Str::slug($value);
        if ($this->slug === '' || $this->slug === $this->autoSlug) {
            $this->slug = $generated;
            $this->autoSlug = $generated;
        }
    }

    /** Azzera il pattern non selezionato quando si cambia tipo */
    public function updatedPatternType(): void
    {
        if ($this->patternType === 'compound_pattern') {
            $this->jointActionId = null;
        } else {
            $this->compoundPatternId = null;
        }
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'nameIt' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:128', Rule::unique('exercises', 'slug')->ignore($this->exerciseId)->whereNull('deleted_at')],
            'description' => ['nullable', 'string'],
            'executionDescription' => ['nullable', 'string'],
            'patternType' => ['required', 'in:compound_pattern,joint_action'],
            'compoundPatternId' => ['required_if:patternType,compound_pattern', 'nullable', 'exists:movement_patterns,id'],
            'jointActionId' => ['required_if:patternType,joint_action', 'nullable', 'exists:movement_patterns,id'],
            'mechanic' => ['required', 'in:compound,isolation'],
            'plane' => ['required', 'in:sagittal,frontal,transverse,multiplanar'],
            'laterality' => ['required', 'in:bilateral,unilateral_alternating,unilateral_isolated'],
            'skillLevel' => ['required', 'in:beginner,intermediate,advanced'],
            'measurementType' => ['required', 'in:reps_weight,reps_only,time,time_weight,distance,isometric_hold'],
            'videoUrl' => ['nullable', 'url', 'max:512'],
            'thumbnailUrl' => ['nullable', 'url', 'max:512'],
            'imageFile' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'selectedEquipment' => ['array'],
            'selectedEquipment.*' => ['exists:equipment,id'],
            'muscleData' => ['array'],
            'muscleData.*.role' => ['nullable', 'in:primary,secondary,stabilizer'],
            'muscleData.*.pct' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'compoundPatternId.required_if' => 'Seleziona un compound pattern.',
            'jointActionId.required_if' => 'Seleziona una joint action.',
        ];
    }

    /** Validazione custom XOR e muscoli */
    protected function validateMuscles(): void
    {
        // Almeno un muscolo primary selezionato
        $hasPrimary = collect($this->muscleData)
            ->filter(fn ($d) => $d['selected'] && $d['role'] === 'primary')
            ->isNotEmpty();

        if (! $hasPrimary) {
            $this->addError('muscleData', 'Seleziona almeno un muscolo primary.');
        }

        // Ogni primary selezionato deve avere pct > 0
        collect($this->muscleData)
            ->filter(fn ($d) => $d['selected'] && $d['role'] === 'primary')
            ->each(function ($d, $muscleId) {
                if ($d['pct'] <= 0) {
                    $this->addError("muscleData.{$muscleId}.pct", 'Il contributo dei muscoli primary deve essere > 0.');
                }
            });
    }

    public function save(): void
    {
        $this->validate();
        $this->validateMuscles();

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        DB::transaction(function () {
            $data = [
                'slug' => $this->slug,
                'name_it' => $this->nameIt,
                'description' => $this->description ?: null,
                'execution_description' => $this->executionDescription ?: null,
                'compound_pattern_id' => $this->patternType === 'compound_pattern' ? $this->compoundPatternId : null,
                'joint_action_id' => $this->patternType === 'joint_action' ? $this->jointActionId : null,
                'mechanic' => $this->mechanic,
                'plane' => $this->plane,
                'laterality' => $this->laterality,
                'skill_level' => $this->skillLevel,
                'measurement_type' => $this->measurementType,
                'video_url' => $this->videoUrl ?: null,
                'thumbnail_url' => $this->thumbnailUrl ?: null,
                'created_by' => ($this->exercise !== null && $this->exercise->exists) ? $this->exercise->created_by : auth()->id(),
            ];

            if ($this->exercise !== null && $this->exercise->exists) {
                $this->exercise->update($data);
                $exercise = $this->exercise;
            } else {
                $exercise = Exercise::create($data);
            }

            // Salva immagine locale se caricata
            if ($this->imageFile !== null) {
                $ext = $this->imageFile->getClientOriginalExtension() ?: 'png';
                $dir = public_path('images/exercises');
                if (! is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                $this->imageFile->move($dir, $exercise->slug.'.'.$ext);
            }

            // Sync attrezzatura
            $exercise->equipment()->sync($this->selectedEquipment);

            // Sync muscoli: costruisce array pivot [muscle_id => [role, contribution_pct]]
            $muscleSync = [];
            foreach ($this->muscleData as $muscleId => $d) {
                if ($d['selected']) {
                    $muscleSync[(int) $muscleId] = [
                        'role' => $d['role'],
                        'contribution_pct' => (int) $d['pct'],
                    ];
                }
            }
            $exercise->muscles()->sync($muscleSync);

            if ($this->exerciseId === null) {
                $this->redirect(route('backoffice.exercises.show', $exercise), navigate: false);
            } else {
                session()->flash('success', 'Esercizio aggiornato con successo.');
                $this->redirect(route('backoffice.exercises.show', $exercise), navigate: false);
            }
        });
    }

    public function archive(): void
    {
        if ($this->exercise === null || ! $this->exercise->exists) {
            return;
        }

        $this->exercise->delete();
        session()->flash('success', 'Esercizio archiviato.');
        $this->redirect(route('backoffice.exercises.index'), navigate: false);
    }

    public function render(): View
    {
        return view('livewire.backoffice.exercises.exercise-form', [
            'compoundPatterns' => MovementPattern::compoundPatterns()->orderBy('display_order')->get(),
            'jointActions' => MovementPattern::jointActions()->orderBy('display_order')->get(),
            'allMuscles' => Muscle::orderBy('muscle_group')->orderBy('display_order')->get(),
            'allEquipment' => Equipment::orderBy('name_it')->get(),
        ])->layout('layouts.backoffice')
            ->layoutData(['page_title' => $this->exerciseId ? 'Modifica esercizio' : 'Nuovo esercizio']);
    }
}
