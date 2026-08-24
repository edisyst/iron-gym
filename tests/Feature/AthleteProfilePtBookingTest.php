<?php

use App\Livewire\Athlete\Profile;
use App\Models\Member;
use App\Models\PtBooking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);

    $this->athleteUser = User::factory()->create()->assignRole('atleta');
    $this->member = Member::factory()->create(['user_id' => $this->athleteUser->id]);
    $this->trainer = User::factory()->create()->assignRole('trainer');
});

it('tab PT mostra sessione futura confermata', function () {
    PtBooking::factory()->create([
        'member_id' => $this->member->id,
        'trainer_id' => $this->trainer->id,
        'booked_date' => now()->addDays(3)->toDateString(),
        'start_time' => '10:00:00',
        'status' => 'confirmed',
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'pt')
        ->assertSee($this->trainer->name)
        ->assertSee('Confermata');
});

it('tab PT mostra sessione futura in attesa', function () {
    PtBooking::factory()->create([
        'member_id' => $this->member->id,
        'trainer_id' => $this->trainer->id,
        'booked_date' => now()->addDays(1)->toDateString(),
        'start_time' => '09:00:00',
        'status' => 'pending',
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'pt')
        ->assertSee('In attesa');
});

it('tab PT mostra storico sessioni passate', function () {
    PtBooking::factory()->create([
        'member_id' => $this->member->id,
        'trainer_id' => $this->trainer->id,
        'booked_date' => now()->subDays(5)->toDateString(),
        'start_time' => '11:00:00',
        'status' => 'completed',
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'pt')
        ->assertSee('Completata');
});

it('tab PT non mostra sessioni PT di altri atleti', function () {
    $otherUser = User::factory()->create()->assignRole('atleta');
    $otherMember = Member::factory()->create(['user_id' => $otherUser->id]);

    PtBooking::factory()->create([
        'member_id' => $otherMember->id,
        'trainer_id' => $this->trainer->id,
        'booked_date' => now()->addDays(2)->toDateString(),
        'start_time' => '10:00:00',
        'status' => 'confirmed',
    ]);

    $component = Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'pt');

    expect($component->get('upcomingPtBookings'))->toBeEmpty();
    expect($component->get('pastPtBookings'))->toBeEmpty();
});

it('tab PT mostra stato vuoto senza sessioni', function () {
    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'pt')
        ->assertSee('Nessuna sessione PT in programma');
});
