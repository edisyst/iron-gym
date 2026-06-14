<?php

namespace App\Livewire\Backoffice\Mesocycles;

use App\Models\AthleteVolumeLandmark;
use App\Models\Muscle;
use App\Models\User;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Volume landmarks atleta')]
class VolumeLandmarkManager extends Component
{
    public int $athleteId;

    /** @var array<string, array{mev: int, mav_min: int, mav_max: int, mrv: int}> */
    public array $landmarks = [];

    public function mount(int $athleteId): void
    {
        $this->athleteId = $athleteId;
        $this->loadLandmarks();
    }

    private function loadLandmarks(): void
    {
        $defaults = config('volume_landmarks', []);

        $dbLandmarks = AthleteVolumeLandmark::where('athlete_id', $this->athleteId)
            ->with('muscle')
            ->get()
            ->keyBy(fn ($lm) => $lm->muscle->slug);

        $this->landmarks = [];

        foreach ($defaults as $slug => $default) {
            $db = $dbLandmarks[$slug] ?? null;
            $this->landmarks[$slug] = [
                'mev' => $db ? $db->mev : $default['mev'],
                'mav_min' => $db ? $db->mav_min : $default['mav_min'],
                'mav_max' => $db ? $db->mav_max : $default['mav_max'],
                'mrv' => $db ? $db->mrv : $default['mrv'],
            ];
        }
    }

    public function save(): void
    {
        $this->validate($this->buildRules());

        $muscles = Muscle::whereIn('slug', array_keys($this->landmarks))->get()->keyBy('slug');

        foreach ($this->landmarks as $slug => $values) {
            $muscle = $muscles[$slug] ?? null;
            if ($muscle === null) {
                continue;
            }

            AthleteVolumeLandmark::updateOrCreate(
                ['athlete_id' => $this->athleteId, 'muscle_id' => $muscle->id],
                [
                    'mev' => $values['mev'],
                    'mav_min' => $values['mav_min'],
                    'mav_max' => $values['mav_max'],
                    'mrv' => $values['mrv'],
                    'updated_by' => auth()->id(),
                ]
            );
        }

        session()->flash('success', 'Volume landmarks salvati.');
    }

    public function resetToDefaults(): void
    {
        $muscles = Muscle::whereIn('slug', array_keys(config('volume_landmarks', [])))->get();

        AthleteVolumeLandmark::where('athlete_id', $this->athleteId)
            ->whereIn('muscle_id', $muscles->pluck('id'))
            ->delete();

        $this->loadLandmarks();

        session()->flash('success', 'Ripristinati i valori di default.');
    }

    /** @return array<string, string> */
    private function buildRules(): array
    {
        $rules = [];
        foreach (array_keys($this->landmarks) as $slug) {
            $rules["landmarks.{$slug}.mev"] = 'required|integer|min:0|max:50';
            $rules["landmarks.{$slug}.mav_min"] = 'required|integer|min:0|max:50';
            $rules["landmarks.{$slug}.mav_max"] = 'required|integer|min:0|max:60';
            $rules["landmarks.{$slug}.mrv"] = 'required|integer|min:0|max:60';
        }

        return $rules;
    }

    public function render(): View
    {
        $athlete = User::findOrFail($this->athleteId);

        $muscleNames = Muscle::whereIn('slug', array_keys($this->landmarks))
            ->pluck('name_it', 'slug');

        $muscleGroups = Muscle::whereIn('slug', array_keys($this->landmarks))
            ->pluck('muscle_group', 'slug');

        $grouped = collect($this->landmarks)
            ->map(fn ($lm, $slug) => array_merge($lm, ['name_it' => $muscleNames[$slug] ?? $slug, 'group' => $muscleGroups[$slug] ?? 'altro']))
            ->groupBy('group');

        return view('livewire.backoffice.mesocycles.volume-landmark-manager', [
            'athlete' => $athlete,
            'grouped' => $grouped,
        ])->layout('layouts.backoffice', ['page_title' => 'Volume landmarks — '.$athlete->name]);
    }
}
