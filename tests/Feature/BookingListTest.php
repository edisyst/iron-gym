<?php

use App\Livewire\Backoffice\Calendar\BookingList;
use App\Models\Member;
use App\Models\PtBooking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'gestore', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);

    $this->gestore = User::factory()->create()->assignRole('gestore');
    $this->trainer = User::factory()->create()->assignRole('trainer');
    $this->otherTrainer = User::factory()->create()->assignRole('trainer');

    $athleteUser = User::factory()->create()->assignRole('atleta');
    $this->member = Member::factory()->create(['user_id' => $athleteUser->id]);

    // Prenotazione pending assegnata a $trainer
    $this->booking = PtBooking::factory()->create([
        'trainer_id' => $this->trainer->id,
        'member_id' => $this->member->id,
        'booked_date' => now()->addDays(3)->toDateString(),
        'start_time' => '10:00:00',
        'status' => 'pending',
    ]);
});

it('gestore può confermare qualsiasi prenotazione', function () {
    Livewire::actingAs($this->gestore)
        ->test(BookingList::class)
        ->call('confirm', $this->booking->id);

    expect($this->booking->fresh()->status)->toBe('confirmed');
});

it('trainer può confermare solo le proprie prenotazioni', function () {
    Livewire::actingAs($this->trainer)
        ->test(BookingList::class)
        ->call('confirm', $this->booking->id);

    expect($this->booking->fresh()->status)->toBe('confirmed');
});

it('trainer non può confermare prenotazioni di altri trainer', function () {
    Livewire::actingAs($this->otherTrainer)
        ->test(BookingList::class)
        ->call('confirm', $this->booking->id);

    // Update non applicato perché il filtro trainer_id non corrisponde
    expect($this->booking->fresh()->status)->toBe('pending');
});

it('gestore può annullare qualsiasi prenotazione', function () {
    $this->booking->update(['status' => 'confirmed']);

    Livewire::actingAs($this->gestore)
        ->test(BookingList::class)
        ->set('cancelBookingId', $this->booking->id)
        ->set('cancelReason', 'Gestore ha annullato per motivi operativi')
        ->call('cancel');

    expect($this->booking->fresh()->status)->toBe('cancelled');
});

it('trainer non può annullare prenotazioni di altri trainer', function () {
    $this->booking->update(['status' => 'confirmed']);

    Livewire::actingAs($this->otherTrainer)
        ->test(BookingList::class)
        ->set('cancelBookingId', $this->booking->id)
        ->set('cancelReason', 'Tentativo non autorizzato di annullamento')
        ->call('cancel')
        ->assertStatus(403);
});

it('cancel richiede motivo di almeno 5 caratteri', function () {
    Livewire::actingAs($this->trainer)
        ->test(BookingList::class)
        ->set('cancelBookingId', $this->booking->id)
        ->set('cancelReason', 'No')
        ->call('cancel')
        ->assertHasErrors(['cancelReason']);
});

it('gestore può ripristinare prenotazione annullata', function () {
    $this->booking->update(['status' => 'cancelled']);

    Livewire::actingAs($this->gestore)
        ->test(BookingList::class)
        ->call('restore', $this->booking->id);

    expect($this->booking->fresh()->status)->toBe('pending');
});
