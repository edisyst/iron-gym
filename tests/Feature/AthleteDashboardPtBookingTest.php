<?php

use App\Livewire\Athlete\Dashboard;
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

it('mostra sessione PT futura confermata', function () {
    PtBooking::factory()->create([
        'member_id' => $this->member->id,
        'trainer_id' => $this->trainer->id,
        'booked_date' => now()->addDays(2)->toDateString(),
        'start_time' => '10:00:00',
        'status' => 'confirmed',
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Dashboard::class)
        ->assertSee('Prossime sessioni PT');
});

it('non mostra sessioni PT passate', function () {
    PtBooking::factory()->create([
        'member_id' => $this->member->id,
        'trainer_id' => $this->trainer->id,
        'booked_date' => now()->subDays(2)->toDateString(),
        'start_time' => '10:00:00',
        'status' => 'confirmed',
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Dashboard::class)
        ->assertDontSee('Prossime sessioni PT');
});

it('non mostra sessioni PT cancellate', function () {
    PtBooking::factory()->create([
        'member_id' => $this->member->id,
        'trainer_id' => $this->trainer->id,
        'booked_date' => now()->addDays(2)->toDateString(),
        'start_time' => '10:00:00',
        'status' => 'cancelled',
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Dashboard::class)
        ->assertDontSee('Prossime sessioni PT');
});

it('upcomingPtBookings non carica prenotazioni di altri atleti', function () {
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
        ->test(Dashboard::class);

    expect($component->get('upcomingPtBookings'))->toBeEmpty();
});
