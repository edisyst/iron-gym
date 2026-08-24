<?php

use App\Livewire\Backoffice\Mesocycles\VolumeLandmarkManager;
use App\Models\AthleteVolumeLandmark;
use App\Models\Mesocycle;
use App\Models\Muscle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'gestore', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);

    $this->athlete = User::factory()->create()->assignRole('atleta');
    $this->trainer = User::factory()->create()->assignRole('trainer');
    $this->gestore = User::factory()->create()->assignRole('gestore');

    $this->muscle = Muscle::factory()->create([
        'slug' => 'quadriceps',
        'name_it' => 'Quadricipite',
        'muscle_group' => 'legs',
    ]);

    // Limita il config ai soli slug presenti nel DB per i test
    config(['volume_landmarks' => [
        'quadriceps' => ['mev' => 8, 'mav_min' => 12, 'mav_max' => 20, 'mrv' => 24],
    ]]);
});

it('gestore può caricare i landmarks di qualsiasi atleta', function () {
    Livewire::actingAs($this->gestore)
        ->test(VolumeLandmarkManager::class, ['athleteId' => $this->athlete->id])
        ->assertOk();
});

it('trainer con mesociclo assegnato può accedere ai landmarks', function () {
    Mesocycle::factory()->create([
        'athlete_id' => $this->athlete->id,
        'trainer_id' => $this->trainer->id,
    ]);

    Livewire::actingAs($this->trainer)
        ->test(VolumeLandmarkManager::class, ['athleteId' => $this->athlete->id])
        ->assertOk();
});

it('trainer senza mesociclo assegnato viene respinto con 403', function () {
    $otherTrainer = User::factory()->create()->assignRole('trainer');

    Livewire::actingAs($otherTrainer)
        ->test(VolumeLandmarkManager::class, ['athleteId' => $this->athlete->id])
        ->assertStatus(403);
});

it('save() persiste i landmarks nel database', function () {
    $component = Livewire::actingAs($this->gestore)
        ->test(VolumeLandmarkManager::class, ['athleteId' => $this->athlete->id]);

    $component->set('landmarks.quadriceps.mev', 10)
        ->set('landmarks.quadriceps.mav_min', 14)
        ->set('landmarks.quadriceps.mav_max', 22)
        ->set('landmarks.quadriceps.mrv', 26)
        ->call('save');

    $lm = AthleteVolumeLandmark::where('athlete_id', $this->athlete->id)
        ->whereHas('muscle', fn ($q) => $q->where('slug', 'quadriceps'))
        ->first();

    expect($lm)->not->toBeNull()
        ->and($lm->mev)->toBe(10)
        ->and($lm->mrv)->toBe(26);
});

it('resetToDefaults() elimina i landmarks personalizzati', function () {
    AthleteVolumeLandmark::create([
        'athlete_id' => $this->athlete->id,
        'muscle_id' => $this->muscle->id,
        'mev' => 15,
        'mav_min' => 20,
        'mav_max' => 28,
        'mrv' => 30,
        'updated_by' => $this->gestore->id,
    ]);

    Livewire::actingAs($this->gestore)
        ->test(VolumeLandmarkManager::class, ['athleteId' => $this->athlete->id])
        ->call('resetToDefaults');

    expect(AthleteVolumeLandmark::where('athlete_id', $this->athlete->id)->exists())->toBeFalse();
});

it('save() carica i valori di default se non ci sono landmarks personalizzati', function () {
    $component = Livewire::actingAs($this->gestore)
        ->test(VolumeLandmarkManager::class, ['athleteId' => $this->athlete->id]);

    expect($component->get('landmarks')['quadriceps']['mev'])->toBe(8)
        ->and($component->get('landmarks')['quadriceps']['mrv'])->toBe(24);
});
