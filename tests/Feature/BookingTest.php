<?php

use App\Exceptions\BookingException;
use App\Models\GroupClass;
use App\Models\Member;
use App\Models\TrainerAvailability;
use App\Models\User;
use App\Services\ClassBookingService;
use App\Services\PtBookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Crea le permission/role necessarie (spatie)
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);

    $this->trainer = User::factory()->create();
    $this->trainer->assignRole('trainer');

    $this->member = Member::factory()->create();

    // Disponibilità ricorrente: lunedì (0), 09:00-18:00
    TrainerAvailability::create([
        'trainer_id' => $this->trainer->id,
        'day_of_week' => 0,
        'specific_date' => null,
        'start_time' => '09:00:00',
        'end_time' => '18:00:00',
        'is_available' => true,
    ]);

    // Data di test: lunedì della settimana dopo (garantisce >7gg, deadline 24h sempre futura)
    $this->testDate = Carbon::parse('next monday')->addWeek();

    $this->ptService = app(PtBookingService::class);
    $this->classService = app(ClassBookingService::class);
});

it('una prenotazione PT viene confermata se lo slot è disponibile', function () {
    $booking = $this->ptService->book(
        trainerId: $this->trainer->id,
        memberId: $this->member->id,
        date: $this->testDate,
        startTime: '10:00',
        endTime: '11:00',
    );

    expect($booking->status)->toBe('confirmed');
    expect($booking->trainer_id)->toBe($this->trainer->id);
    expect($booking->member_id)->toBe($this->member->id);
});

it('una prenotazione PT fallisce se lo slot è già occupato', function () {
    // Prima prenotazione — deve andare a buon fine
    $this->ptService->book(
        trainerId: $this->trainer->id,
        memberId: $this->member->id,
        date: $this->testDate,
        startTime: '10:00',
        endTime: '11:00',
    );

    // Seconda prenotazione sovrapposta — deve lanciare eccezione
    expect(fn () => $this->ptService->book(
        trainerId: $this->trainer->id,
        memberId: $this->member->id,
        date: $this->testDate,
        startTime: '10:30',
        endTime: '11:30',
    ))->toThrow(BookingException::class);
});

it('un membro viene messo in waitlist se il corso è pieno', function () {
    $class = GroupClass::factory()->create([
        'trainer_id' => $this->trainer->id,
        'max_participants' => 1,
        'scheduled_at' => now()->addDays(3),
    ]);

    $member2 = Member::factory()->create();

    // Primo membro: posto disponibile
    $this->classService->enroll($class, $this->member);

    // Secondo membro: corso pieno → waitlist
    $waitlisted = $this->classService->enroll($class, $member2);

    expect($waitlisted->status)->toBe('waitlisted');
    expect($waitlisted->position)->toBe(1);
});

it('cancellare una prenotazione confirmed promuove il primo in waitlist', function () {
    $class = GroupClass::factory()->create([
        'trainer_id' => $this->trainer->id,
        'max_participants' => 1,
        'scheduled_at' => now()->addDays(3),
    ]);

    $member2 = Member::factory()->create();

    $confirmed = $this->classService->enroll($class, $this->member);
    $waitlisted = $this->classService->enroll($class, $member2);

    // Cancella l'iscrizione confermata
    $this->classService->cancel($confirmed);

    // Il primo in waitlist deve essere promosso
    expect($waitlisted->fresh()->status)->toBe('confirmed');
    expect($waitlisted->fresh()->position)->toBeNull();
});

it('la cancellation_deadline è 24 ore prima dell\'orario prenotato', function () {
    $booking = $this->ptService->book(
        trainerId: $this->trainer->id,
        memberId: $this->member->id,
        date: $this->testDate,
        startTime: '10:00',
        endTime: '11:00',
    );

    $expectedDeadline = Carbon::parse($this->testDate->toDateString().' 10:00')->subHours(24);

    expect($booking->cancellation_deadline->toDateTimeString())
        ->toBe($expectedDeadline->toDateTimeString());
});

it('iscriversi due volte allo stesso corso lancia BookingException', function () {
    $class = GroupClass::factory()->create([
        'trainer_id' => $this->trainer->id,
        'max_participants' => 10,
        'scheduled_at' => now()->addDays(3),
    ]);

    $this->classService->enroll($class, $this->member);

    expect(fn () => $this->classService->enroll($class, $this->member))
        ->toThrow(BookingException::class);
});

it('canBeCancelledFree restituisce true se now è prima della deadline', function () {
    $booking = $this->ptService->book(
        trainerId: $this->trainer->id,
        memberId: $this->member->id,
        date: $this->testDate,
        startTime: '10:00',
        endTime: '11:00',
    );

    // La deadline è 24h prima del lunedì alle 10:00, quindi in futuro
    expect($booking->canBeCancelledFree())->toBeTrue();
});

it('cancellare una prenotazione in stato cancelled lancia BookingException', function () {
    $booking = $this->ptService->book(
        trainerId: $this->trainer->id,
        memberId: $this->member->id,
        date: $this->testDate,
        startTime: '10:00',
        endTime: '11:00',
    );

    // Prima cancellazione: ok
    $this->ptService->cancel($booking, $this->trainer);

    // Seconda cancellazione: deve lanciare eccezione
    expect(fn () => $this->ptService->cancel($booking->fresh(), $this->trainer))
        ->toThrow(BookingException::class);
});
