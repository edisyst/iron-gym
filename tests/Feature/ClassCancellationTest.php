<?php

use App\Jobs\NotifyClassCancellation;
use App\Livewire\Backoffice\Calendar\GroupClassManager;
use App\Models\ClassOccurrence;
use App\Models\Member;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\ClassOccurrenceCancelledNotification;
use App\Services\ClassBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
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
    $this->receptionist = User::factory()->create()->assignRole('receptionist');

    $this->occurrence = ClassOccurrence::factory()->create([
        'trainer_id' => $this->trainer->id,
        'capacity' => 10,
        'date' => today()->addDays(3)->toDateString(),
        'status' => 'planned',
    ]);

    // Membro con account atleta, abbonamento e cert
    $this->athleteUser = User::factory()->create()->assignRole('atleta');
    $this->member = Member::factory()->create([
        'user_id' => $this->athleteUser->id,
        'medical_cert_expiry' => now()->addYear(),
    ]);
    Subscription::factory()->create([
        'member_id' => $this->member->id,
        'started_at' => today()->subDays(10)->toDateString(),
        'expires_at' => today()->addDays(30)->toDateString(),
        'status' => 'active',
    ]);

    $svc = app(ClassBookingService::class);
    $this->booking = $svc->enroll($this->occurrence, $this->member);
});

// -------------------------------------------------------------------------
// Notifica cancellazione
// -------------------------------------------------------------------------

it('deleteClass con iscritti dispatchea NotifyClassCancellation', function () {
    Queue::fake();

    Livewire::actingAs($this->gestore)
        ->test(GroupClassManager::class)
        ->call('deleteClass', $this->occurrence->id);

    Queue::assertPushed(NotifyClassCancellation::class, function ($job) {
        return $job->occurrence->id === $this->occurrence->id;
    });
});

it('deleteClass senza iscritti non dispatchea la notifica', function () {
    Queue::fake();

    $this->booking->update(['status' => 'cancelled_by_athlete']);

    Livewire::actingAs($this->gestore)
        ->test(GroupClassManager::class)
        ->call('deleteClass', $this->occurrence->id);

    Queue::assertNotPushed(NotifyClassCancellation::class);
});

it('NotifyClassCancellation notifica i membri confermati con account', function () {
    Notification::fake();

    $job = new NotifyClassCancellation($this->occurrence);
    $job->handle();

    Notification::assertSentTo($this->athleteUser, ClassOccurrenceCancelledNotification::class);
});

it('NotifyClassCancellation salta membri senza account utente', function () {
    Notification::fake();

    // Membro senza user_id
    $memberNoUser = Member::factory()->create(['user_id' => null, 'medical_cert_expiry' => now()->addYear()]);
    Subscription::factory()->create([
        'member_id' => $memberNoUser->id,
        'started_at' => today()->subDays(5)->toDateString(),
        'expires_at' => today()->addDays(30)->toDateString(),
        'status' => 'active',
    ]);
    app(ClassBookingService::class)->enroll($this->occurrence, $memberNoUser);

    $job = new NotifyClassCancellation($this->occurrence);
    $job->handle();

    // Notifica arriva solo all'atleta con account
    Notification::assertSentTo($this->athleteUser, ClassOccurrenceCancelledNotification::class);
    Notification::assertCount(1, ClassOccurrenceCancelledNotification::class);
});

it('NotifyClassCancellation salta prenotazioni in waitlist', function () {
    Notification::fake();

    // Riempi l'occorrenza
    $this->occurrence->update(['capacity' => 1]);

    $athlete2 = User::factory()->create()->assignRole('atleta');
    $member2 = Member::factory()->create(['user_id' => $athlete2->id, 'medical_cert_expiry' => now()->addYear()]);
    Subscription::factory()->create([
        'member_id' => $member2->id,
        'started_at' => today()->subDays(5)->toDateString(),
        'expires_at' => today()->addDays(30)->toDateString(),
        'status' => 'active',
    ]);

    // Secondo enroll → waitlist
    $waitlistBooking = app(ClassBookingService::class)->enroll($this->occurrence, $member2);
    expect($waitlistBooking->status)->toBe('waitlisted');

    $job = new NotifyClassCancellation($this->occurrence);
    $job->handle();

    // Solo il confermato riceve la notifica
    Notification::assertSentTo($this->athleteUser, ClassOccurrenceCancelledNotification::class);
    Notification::assertNotSentTo($athlete2, ClassOccurrenceCancelledNotification::class);
});

// -------------------------------------------------------------------------
// Check-in receptionist
// -------------------------------------------------------------------------

it('receptionist può markAttended su una prenotazione', function () {
    Livewire::actingAs($this->receptionist)
        ->test(GroupClassManager::class)
        ->call('markAttended', $this->booking->id);

    expect($this->booking->fresh()->attended_at)->not->toBeNull();
    expect($this->booking->fresh()->status)->toBe('confirmed');
});

it('receptionist può markNoShow su una prenotazione', function () {
    Livewire::actingAs($this->receptionist)
        ->test(GroupClassManager::class)
        ->call('markNoShow', $this->booking->id);

    $b = $this->booking->fresh();
    expect($b->status)->toBe('no_show');
    expect($b->attended_at)->toBeNull();
});
