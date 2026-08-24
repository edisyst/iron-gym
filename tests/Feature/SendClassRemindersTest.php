<?php

use App\Jobs\SendClassReminders;
use App\Models\ClassBooking;
use App\Models\ClassOccurrence;
use App\Models\GroupClass;
use App\Models\Member;
use App\Models\User;
use App\Notifications\ClassReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);

    $this->groupClass = GroupClass::factory()->create(['name' => 'Pilates', 'is_active' => true]);

    $user = User::factory()->create()->assignRole('atleta');
    $this->member = Member::factory()->create(['user_id' => $user->id]);
});

it('invia reminder per corsi confermati del giorno dopo', function () {
    Notification::fake();

    $occurrence = ClassOccurrence::factory()->create([
        'group_class_id' => $this->groupClass->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '10:00:00',
        'status' => 'planned',
    ]);

    ClassBooking::factory()->create([
        'class_occurrence_id' => $occurrence->id,
        'member_id' => $this->member->id,
        'status' => 'confirmed',
    ]);

    (new SendClassReminders)->handle();

    Notification::assertSentTo($this->member->user, ClassReminderNotification::class);
});

it('non invia reminder per occorrenze di oggi', function () {
    Notification::fake();

    $occurrence = ClassOccurrence::factory()->create([
        'group_class_id' => $this->groupClass->id,
        'date' => now()->toDateString(),
        'start_time' => '10:00:00',
        'status' => 'planned',
    ]);

    ClassBooking::factory()->create([
        'class_occurrence_id' => $occurrence->id,
        'member_id' => $this->member->id,
        'status' => 'confirmed',
    ]);

    (new SendClassReminders)->handle();

    Notification::assertNothingSent();
});

it('non invia reminder per prenotazioni waitlisted', function () {
    Notification::fake();

    $occurrence = ClassOccurrence::factory()->create([
        'group_class_id' => $this->groupClass->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '10:00:00',
        'status' => 'planned',
    ]);

    ClassBooking::factory()->create([
        'class_occurrence_id' => $occurrence->id,
        'member_id' => $this->member->id,
        'status' => 'waitlisted',
    ]);

    (new SendClassReminders)->handle();

    Notification::assertNothingSent();
});

it('non invia reminder per occorrenze cancellate', function () {
    Notification::fake();

    $occurrence = ClassOccurrence::factory()->create([
        'group_class_id' => $this->groupClass->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '10:00:00',
        'status' => 'cancelled',
    ]);

    ClassBooking::factory()->create([
        'class_occurrence_id' => $occurrence->id,
        'member_id' => $this->member->id,
        'status' => 'confirmed',
    ]);

    (new SendClassReminders)->handle();

    Notification::assertNothingSent();
});

it('notifica contiene nome corso e orario', function () {
    Notification::fake();

    $occurrence = ClassOccurrence::factory()->create([
        'group_class_id' => $this->groupClass->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '10:30:00',
        'status' => 'planned',
    ]);

    ClassBooking::factory()->create([
        'class_occurrence_id' => $occurrence->id,
        'member_id' => $this->member->id,
        'status' => 'confirmed',
    ]);

    (new SendClassReminders)->handle();

    Notification::assertSentTo(
        $this->member->user,
        ClassReminderNotification::class,
        function (ClassReminderNotification $notification) {
            $data = $notification->toArray(null);

            return str_contains($data['message'], 'Pilates')
                && str_contains($data['message'], '10:30')
                && $data['type'] === 'class_reminder';
        }
    );
});

it('notifica piu atleti iscritti allo stesso corso', function () {
    Notification::fake();

    $user2 = User::factory()->create()->assignRole('atleta');
    $member2 = Member::factory()->create(['user_id' => $user2->id]);

    $occurrence = ClassOccurrence::factory()->create([
        'group_class_id' => $this->groupClass->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '10:00:00',
        'status' => 'planned',
        'capacity' => 10,
    ]);

    ClassBooking::factory()->create([
        'class_occurrence_id' => $occurrence->id,
        'member_id' => $this->member->id,
        'status' => 'confirmed',
    ]);
    ClassBooking::factory()->create([
        'class_occurrence_id' => $occurrence->id,
        'member_id' => $member2->id,
        'status' => 'confirmed',
    ]);

    (new SendClassReminders)->handle();

    Notification::assertSentTo($this->member->user, ClassReminderNotification::class);
    Notification::assertSentTo($user2, ClassReminderNotification::class);
});
