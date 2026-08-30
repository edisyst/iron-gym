<?php

use App\Livewire\Athlete\Profile;
use App\Models\Exercise;
use App\Models\ExerciseSet;
use App\Models\PersonalRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);

    Feature::activate('personal_records');

    $this->athleteUser = User::factory()->create()->assignRole('atleta');
    $this->exercise = Exercise::factory()->create(['name_it' => 'Squat']);
    $this->exerciseSet = ExerciseSet::factory()->create();
});

function makePr(int $athleteId, int $exerciseId, int $exerciseSetId, float $value, string $achievedAt): void
{
    PersonalRecord::create([
        'athlete_id' => $athleteId,
        'exercise_id' => $exerciseId,
        'exercise_set_id' => $exerciseSetId,
        'record_type' => 'e1rm',
        'value' => $value,
        'achieved_at' => $achievedAt,
    ]);
}

it('tab record mostra PR recente con nome esercizio e valore', function () {
    makePr($this->athleteUser->id, $this->exercise->id, $this->exerciseSet->id, 120.5, now()->subDays(2)->toDateTimeString());

    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'record')
        ->assertSee('Squat')
        ->assertSee('120.5');
});

it('tab record mostra stato vuoto senza PR', function () {
    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'record')
        ->assertSee('Nessun record registrato');
});

it('tab record non mostra oltre 5 PR', function () {
    foreach (range(1, 7) as $i) {
        makePr(
            $this->athleteUser->id,
            $this->exercise->id,
            $this->exerciseSet->id,
            100 + $i,
            now()->subDays($i)->toDateTimeString()
        );
    }

    // PR più vecchi (subDays 6 e 7) con valore 106 e 107 non mostrati
    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'record')
        ->assertSee('101')
        ->assertDontSee('106.0');
});

it('tab record non mostra PR di altri atleti', function () {
    $otherUser = User::factory()->create()->assignRole('atleta');
    makePr($otherUser->id, $this->exercise->id, $this->exerciseSet->id, 200.0, now()->toDateTimeString());

    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'record')
        ->assertSee('Nessun record registrato');
});

it('tab record mostra link a pagina record completa', function () {
    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'record')
        ->assertSee('Vedi tutti');
});
