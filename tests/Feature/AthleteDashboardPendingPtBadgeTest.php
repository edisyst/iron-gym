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

it('mostra il badge In attesa su una sessione PT pending', function () {
    PtBooking::factory()->create([
        'member_id' => $this->member->id,
        'trainer_id' => $this->trainer->id,
        'booked_date' => now()->addDays(3)->toDateString(),
        'start_time' => '17:00:00',
        'status' => 'pending',
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Dashboard::class)
        ->assertSee('Prossime sessioni PT')
        ->assertSee('In attesa');
});

it('non mostra il badge In attesa su una sessione confermata', function () {
    PtBooking::factory()->create([
        'member_id' => $this->member->id,
        'trainer_id' => $this->trainer->id,
        'booked_date' => now()->addDays(3)->toDateString(),
        'start_time' => '17:00:00',
        'status' => 'confirmed',
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Dashboard::class)
        ->assertSee('Prossime sessioni PT')
        ->assertDontSee('In attesa');
});
