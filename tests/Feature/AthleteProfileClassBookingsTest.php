<?php

use App\Livewire\Athlete\Profile;
use App\Models\ClassBooking;
use App\Models\ClassOccurrence;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);

    $this->athleteUser = User::factory()->create()->assignRole('atleta');
    $this->member = Member::factory()->create(['user_id' => $this->athleteUser->id]);

    config(['features.group_classes_enabled' => true]);
    Feature::flushCache();
});

it('tab corsi visibile solo con feature flag attivo', function () {
    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->assertSee('Corsi');
});

it('tab corsi non visibile con feature flag disattivo', function () {
    config(['features.group_classes_enabled' => false]);
    Feature::flushCache();

    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->assertDontSee('Corsi');
});

it('tab corsi mostra prenotazione confermata futura', function () {
    $occ = ClassOccurrence::factory()->create([
        'date'       => now()->addDays(3)->toDateString(),
        'start_time' => '10:00:00',
        'status'     => 'planned',
    ]);

    ClassBooking::factory()->create([
        'class_occurrence_id' => $occ->id,
        'member_id'           => $this->member->id,
        'status'              => 'confirmed',
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'corsi')
        ->assertSee('Confermato');
});

it('tab corsi mostra prenotazione in lista d\'attesa', function () {
    $occ = ClassOccurrence::factory()->create([
        'date'   => now()->addDays(2)->toDateString(),
        'status' => 'planned',
    ]);

    ClassBooking::factory()->waitlisted()->create([
        'class_occurrence_id' => $occ->id,
        'member_id'           => $this->member->id,
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'corsi')
        ->assertSee('Lista d\'attesa');
});

it('tab corsi non mostra prenotazioni di altri atleti', function () {
    $otherMember = Member::factory()->create();
    $occ = ClassOccurrence::factory()->create([
        'date'   => now()->addDays(1)->toDateString(),
        'status' => 'planned',
    ]);

    ClassBooking::factory()->create([
        'class_occurrence_id' => $occ->id,
        'member_id'           => $otherMember->id,
        'status'              => 'confirmed',
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'corsi')
        ->assertSee('Nessun corso prenotato');
});
