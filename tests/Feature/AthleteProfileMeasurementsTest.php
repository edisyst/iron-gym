<?php

use App\Livewire\Athlete\Profile;
use App\Models\BodyMeasurement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);

    $this->athleteUser = User::factory()->create()->assignRole('atleta');
});

it('tab misurazioni mostra misurazione recente', function () {
    BodyMeasurement::factory()->create([
        'athlete_id' => $this->athleteUser->id,
        'measured_at' => now()->subDays(2)->toDateString(),
        'weight_kg' => 80.5,
        'body_fat_pct' => 15.0,
        'waist_cm' => 82.0,
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'misurazioni')
        ->assertSee('80.5')
        ->assertSee('15')
        ->assertSee('82');
});

it('tab misurazioni mostra stato vuoto senza misurazioni', function () {
    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'misurazioni')
        ->assertSee('Nessuna misurazione registrata');
});

it('tab misurazioni non mostra oltre 5 misurazioni', function () {
    // 5 misurazioni recenti
    foreach (range(1, 5) as $i) {
        BodyMeasurement::factory()->create([
            'athlete_id' => $this->athleteUser->id,
            'measured_at' => now()->subDays($i)->toDateString(),
            'weight_kg' => null,
            'body_fat_pct' => null,
            'waist_cm' => null,
            'chest_cm' => null,
        ]);
    }

    // 2 misurazioni più vecchie con note identificative uniche
    foreach (range(6, 7) as $i) {
        BodyMeasurement::factory()->create([
            'athlete_id' => $this->athleteUser->id,
            'measured_at' => now()->subDays($i)->toDateString(),
            'weight_kg' => null,
            'notes' => "misurazione-nascosta-{$i}",
        ]);
    }

    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'misurazioni')
        ->assertDontSee('misurazione-nascosta-6')
        ->assertDontSee('misurazione-nascosta-7');
});

it('tab misurazioni non mostra misurazioni di altri atleti', function () {
    $otherUser = User::factory()->create()->assignRole('atleta');

    BodyMeasurement::factory()->create([
        'athlete_id' => $otherUser->id,
        'measured_at' => now()->toDateString(),
        'weight_kg' => 99.9,
    ]);

    $component = Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'misurazioni');

    expect($component->get('recentMeasurements'))->toBeEmpty();
});

it('tab misurazioni mostra le note se presenti', function () {
    BodyMeasurement::factory()->create([
        'athlete_id' => $this->athleteUser->id,
        'measured_at' => now()->toDateString(),
        'weight_kg' => 75.0,
        'notes' => 'Post vacanze estive',
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'misurazioni')
        ->assertSee('Post vacanze estive');
});
