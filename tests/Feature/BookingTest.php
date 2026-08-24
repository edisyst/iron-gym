<?php

use App\Exceptions\BookingException;
use App\Models\ClassOccurrence;
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
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);

    $this->trainer = User::factory()->create();
    $this->trainer->assignRole('trainer');

    $this->member = Member::factory()->create();

    TrainerAvailability::create([
        'trainer_id'    => $this->trainer->id,
        'day_of_week'   => 0,
        'specific_date' => null,
        'start_time'    => '09:00:00',
        'end_time'      => '18:00:00',
        'is_available'  => true,
    ]);

    $this->testDate = Carbon::parse('next monday')->addWeek();

    $this->ptService    = app(PtBookingService::class);
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
    $this->ptService->book(
        trainerId: $this->trainer->id,
        memberId: $this->member->id,
        date: $this->testDate,
        startTime: '10:00',
        endTime: '11:00',
    );

    expect(fn () => $this->ptService->book(
        trainerId: $this->trainer->id,
        memberId: $this->member->id,
        date: $this->testDate,
        startTime: '10:30',
        endTime: '11:30',
    ))->toThrow(BookingException::class);
});

it('un membro viene messo in waitlist se il corso è pieno', function () {
    $groupClass = GroupClass::factory()->create(['default_capacity' => 1]);
    $occurrence = ClassOccurrence::factory()->create([
        'group_class_id' => $groupClass->id,
        'trainer_id'     => $this->trainer->id,
        'capacity'       => 1,
        'date'           => now()->addDays(3)->toDateString(),
        'status'         => 'planned',
    ]);

    $member2 = Member::factory()->create();

    $this->classService->enroll($occurrence, $this->member);
    $waitlisted = $this->classService->enroll($occurrence, $member2);

    expect($waitlisted->status)->toBe('waitlisted');
    expect($waitlisted->position)->toBe(1);
});

it('cancellare una prenotazione confirmed promuove il primo in waitlist', function () {
    $groupClass = GroupClass::factory()->create(['default_capacity' => 1]);
    $occurrence = ClassOccurrence::factory()->create([
        'group_class_id' => $groupClass->id,
        'trainer_id'     => $this->trainer->id,
        'capacity'       => 1,
        'date'           => now()->addDays(3)->toDateString(),
        'status'         => 'planned',
    ]);

    $member2 = Member::factory()->create();

    $confirmed  = $this->classService->enroll($occurrence, $this->member);
    $waitlisted = $this->classService->enroll($occurrence, $member2);

    $this->classService->cancel($confirmed);

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

it('iscriversi due volte alla stessa occorrenza lancia BookingException', function () {
    $groupClass = GroupClass::factory()->create(['default_capacity' => 10]);
    $occurrence = ClassOccurrence::factory()->create([
        'group_class_id' => $groupClass->id,
        'trainer_id'     => $this->trainer->id,
        'capacity'       => 10,
        'date'           => now()->addDays(3)->toDateString(),
        'status'         => 'planned',
    ]);

    $this->classService->enroll($occurrence, $this->member);

    expect(fn () => $this->classService->enroll($occurrence, $this->member))
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

    expect($booking->canBeCancelledFree())->toBeTrue();
});

it('cancellare una prenotazione PT in stato cancelled lancia BookingException', function () {
    $booking = $this->ptService->book(
        trainerId: $this->trainer->id,
        memberId: $this->member->id,
        date: $this->testDate,
        startTime: '10:00',
        endTime: '11:00',
    );

    $this->ptService->cancel($booking, $this->trainer);

    expect(fn () => $this->ptService->cancel($booking->fresh(), $this->trainer))
        ->toThrow(BookingException::class);
});

// -------------------------------------------------------------------------
// Test model e relazioni ClassOccurrence / ClassSchedule / ClassBooking
// -------------------------------------------------------------------------

it('ClassOccurrence ha le relazioni groupClass, trainer, bookings', function () {
    $groupClass = GroupClass::factory()->create();
    $occurrence = ClassOccurrence::factory()->create([
        'group_class_id' => $groupClass->id,
        'trainer_id'     => $this->trainer->id,
    ]);

    expect($occurrence->groupClass->id)->toBe($groupClass->id);
    expect($occurrence->trainer->id)->toBe($this->trainer->id);
    expect($occurrence->bookings)->toBeEmpty();
});

it('unique (class_occurrence_id, member_id) impedisce la doppia prenotazione a livello DB', function () {
    $occurrence = ClassOccurrence::factory()->create([
        'trainer_id' => $this->trainer->id,
        'capacity'   => 5,
    ]);

    \Illuminate\Support\Facades\DB::table('class_bookings')->insert([
        'class_occurrence_id' => $occurrence->id,
        'member_id'           => $this->member->id,
        'status'              => 'confirmed',
        'position'            => null,
        'created_at'          => now(),
    ]);

    expect(fn () => \Illuminate\Support\Facades\DB::table('class_bookings')->insert([
        'class_occurrence_id' => $occurrence->id,
        'member_id'           => $this->member->id,
        'status'              => 'waitlisted',
        'position'            => 1,
        'created_at'          => now(),
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

it('unique (class_schedule_id, date) impedisce occorrenze duplicate per palinsesto', function () {
    $groupClass = GroupClass::factory()->create();
    $schedule   = \App\Models\ClassSchedule::factory()->create([
        'group_class_id' => $groupClass->id,
        'trainer_id'     => $this->trainer->id,
    ]);

    ClassOccurrence::create([
        'group_class_id'    => $groupClass->id,
        'class_schedule_id' => $schedule->id,
        'date'              => '2026-09-01',
        'start_time'        => '09:00:00',
        'end_time'          => '10:00:00',
        'trainer_id'        => $this->trainer->id,
        'capacity'          => 10,
        'status'            => 'planned',
    ]);

    expect(fn () => ClassOccurrence::create([
        'group_class_id'    => $groupClass->id,
        'class_schedule_id' => $schedule->id,
        'date'              => '2026-09-01',
        'start_time'        => '09:00:00',
        'end_time'          => '10:00:00',
        'trainer_id'        => $this->trainer->id,
        'capacity'          => 10,
        'status'            => 'planned',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

it('occorrenze una tantum (class_schedule_id NULL) non sono coperte dal vincolo unique', function () {
    $groupClass = GroupClass::factory()->create();

    ClassOccurrence::create([
        'group_class_id'    => $groupClass->id,
        'class_schedule_id' => null,
        'date'              => '2026-09-01',
        'start_time'        => '09:00:00',
        'end_time'          => '10:00:00',
        'trainer_id'        => $this->trainer->id,
        'capacity'          => 10,
        'status'            => 'planned',
    ]);

    // La seconda occorrenza con class_schedule_id=NULL NON deve lanciare eccezione
    $second = ClassOccurrence::create([
        'group_class_id'    => $groupClass->id,
        'class_schedule_id' => null,
        'date'              => '2026-09-01',
        'start_time'        => '09:00:00',
        'end_time'          => '10:00:00',
        'trainer_id'        => $this->trainer->id,
        'capacity'          => 10,
        'status'            => 'planned',
    ]);

    expect($second->id)->toBeGreaterThan(0);
});

it('un trainer non nel pivot class_trainer è assegnabile all\'occorrenza ma escluso dalla relazione trainers()', function () {
    $extraTrainer = User::factory()->create();
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);
    $extraTrainer->assignRole('trainer');

    $groupClass = GroupClass::factory()->create();
    // Solo $this->trainer è nel pivot
    $groupClass->trainers()->attach($this->trainer->id);

    $occurrence = ClassOccurrence::factory()->create([
        'group_class_id' => $groupClass->id,
        'trainer_id'     => $extraTrainer->id, // trainer non abilitato a livello definizione
    ]);

    // A livello DB l'occorrenza ha il trainer
    expect($occurrence->trainer->id)->toBe($extraTrainer->id);

    // La relazione GroupClass::trainers() lo esclude (non è nel pivot)
    expect($groupClass->trainers->contains($extraTrainer))->toBeFalse();
    expect($groupClass->trainers->contains($this->trainer))->toBeTrue();
});
