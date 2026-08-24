<?php

use App\Livewire\Athlete\Booking;
use App\Models\ClassOccurrence;
use App\Models\Member;
use App\Models\Subscription;
use App\Models\User;
use App\Services\ClassBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestore', 'guard_name' => 'web']);

    $this->athleteUser = User::factory()->create()->assignRole('atleta');
    $this->member = Member::factory()->create([
        'user_id' => $this->athleteUser->id,
        'medical_cert_expiry' => now()->addYear(),
    ]);
    Subscription::factory()->create([
        'member_id' => $this->member->id,
        'started_at' => today()->subDays(10)->toDateString(),
        'expires_at' => today()->addDays(60)->toDateString(),
        'status' => 'active',
    ]);

    config(['features.group_classes_enabled' => true]);
    Feature::flushCache();
});

it('atleta iscrive correttamente a corso nella finestra di prenotazione', function () {
    $occurrence = ClassOccurrence::factory()->create([
        'date' => today()->addDays(3)->toDateString(),
        'start_time' => '18:00:00',
        'end_time' => '19:00:00',
        'status' => 'planned',
        'capacity' => 10,
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Booking::class)
        ->call('enrollClass', $occurrence->id)
        ->assertSessionHasNoErrors();

    expect($occurrence->confirmedBookings()->where('member_id', $this->member->id)->exists())->toBeTrue();
});

it('atleta non può iscriversi se prenotazione non ancora aperta', function () {
    // booking_opens_days = 7: corso tra 10 giorni → opensAt = oggi+3 → ancora chiuso
    $occurrence = ClassOccurrence::factory()->create([
        'date' => today()->addDays(10)->toDateString(),
        'start_time' => '18:00:00',
        'end_time' => '19:00:00',
        'status' => 'planned',
        'capacity' => 10,
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Booking::class)
        ->call('enrollClass', $occurrence->id);

    // Nessuna iscrizione creata
    expect($occurrence->confirmedBookings()->exists())->toBeFalse();
});

it('atleta non può iscriversi se prenotazione è chiusa (entro booking_closes_minutes)', function () {
    // Corso inizia tra 10 minuti (< 30 min) → prenotazione chiusa
    $start = now()->addMinutes(10)->format('H:i:s');
    $end = now()->addMinutes(70)->format('H:i:s');

    $occurrence = ClassOccurrence::factory()->create([
        'date' => today()->toDateString(),
        'start_time' => $start,
        'end_time' => $end,
        'status' => 'planned',
        'capacity' => 10,
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Booking::class)
        ->call('enrollClass', $occurrence->id);

    expect($occurrence->confirmedBookings()->exists())->toBeFalse();
});

it('atleta non può cancellare iscrizione oltre free_cancel_hours', function () {
    // Corso inizia tra 1 ora (< 3h) → cancellazione non disponibile
    $start = now()->addHour()->format('H:i:s');
    $end = now()->addHours(2)->format('H:i:s');

    $occurrence = ClassOccurrence::factory()->create([
        'date' => today()->toDateString(),
        'start_time' => $start,
        'end_time' => $end,
        'status' => 'planned',
        'capacity' => 10,
    ]);

    // Iscrivi direttamente tramite service (bypass window)
    $booking = app(ClassBookingService::class)->enroll($occurrence, $this->member);
    expect($booking->status)->toBe('confirmed');

    Livewire::actingAs($this->athleteUser)
        ->test(Booking::class)
        ->call('cancelClassBooking', $booking->id);

    // Prenotazione ancora confermata — cancellazione bloccata dalla finestra
    expect($booking->fresh()->status)->toBe('confirmed');
});

it('atleta può cancellare iscrizione entro la finestra gratuita', function () {
    $occurrence = ClassOccurrence::factory()->create([
        'date' => today()->addDays(2)->toDateString(),
        'start_time' => '18:00:00',
        'end_time' => '19:00:00',
        'status' => 'planned',
        'capacity' => 10,
    ]);

    // Iscrivi direttamente tramite service
    $booking = app(ClassBookingService::class)->enroll($occurrence, $this->member);

    Livewire::actingAs($this->athleteUser)
        ->test(Booking::class)
        ->call('cancelClassBooking', $booking->id);

    expect($booking->fresh()->status)->toBe('cancelled_by_athlete');
});
