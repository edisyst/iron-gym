<?php

use App\Livewire\Athlete\Notifications as AthleteNotifications;
use App\Models\TrainingSession;
use App\Models\User;
use App\Notifications\SessionReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);

    $this->athleteUser = User::factory()->create()->assignRole('atleta');
});

it('atleta vede le notifiche ricevute', function () {
    $this->athleteUser->notify(new SessionReminderNotification(
        TrainingSession::factory()->create()
    ));

    Livewire::actingAs($this->athleteUser)
        ->test(AthleteNotifications::class)
        ->assertSee('Hai una sessione programmata oggi');
});

it('atleta non vede notifiche di altri utenti', function () {
    $other = User::factory()->create()->assignRole('atleta');
    $other->notify(new SessionReminderNotification(
        TrainingSession::factory()->create()
    ));

    Livewire::actingAs($this->athleteUser)
        ->test(AthleteNotifications::class)
        ->assertDontSee('Hai una sessione programmata oggi');
});

it('markRead segna una notifica come letta', function () {
    $this->athleteUser->notify(new SessionReminderNotification(
        TrainingSession::factory()->create()
    ));

    $notification = $this->athleteUser->notifications()->first();
    expect($notification->read_at)->toBeNull();

    Livewire::actingAs($this->athleteUser)
        ->test(AthleteNotifications::class)
        ->call('markRead', $notification->id);

    expect($this->athleteUser->notifications()->find($notification->id)->read_at)->not->toBeNull();
});

it('markAllRead segna tutte le notifiche come lette', function () {
    $session = TrainingSession::factory()->create();
    $this->athleteUser->notify(new SessionReminderNotification($session));
    $this->athleteUser->notify(new SessionReminderNotification($session));

    expect($this->athleteUser->unreadNotifications()->count())->toBe(2);

    Livewire::actingAs($this->athleteUser)
        ->test(AthleteNotifications::class)
        ->call('markAllRead');

    expect($this->athleteUser->unreadNotifications()->count())->toBe(0);
});

it('deleteNotification elimina la notifica', function () {
    $this->athleteUser->notify(new SessionReminderNotification(
        TrainingSession::factory()->create()
    ));

    $notification = $this->athleteUser->notifications()->first();

    Livewire::actingAs($this->athleteUser)
        ->test(AthleteNotifications::class)
        ->call('deleteNotification', $notification->id);

    expect($this->athleteUser->notifications()->count())->toBe(0);
});

it('deleteNotification non elimina notifiche di altri utenti', function () {
    $other = User::factory()->create()->assignRole('atleta');
    $other->notify(new SessionReminderNotification(
        TrainingSession::factory()->create()
    ));

    $notification = $other->notifications()->first();

    Livewire::actingAs($this->athleteUser)
        ->test(AthleteNotifications::class)
        ->call('deleteNotification', $notification->id);

    // Notifica dell'altro utente intatta
    expect($other->notifications()->count())->toBe(1);
});

it('endpoint unread-count restituisce conteggio corretto', function () {
    $session = TrainingSession::factory()->create();
    $this->athleteUser->notify(new SessionReminderNotification($session));
    $this->athleteUser->notify(new SessionReminderNotification($session));
    $this->athleteUser->notifications()->first()->update(['read_at' => now()]);

    $this->actingAs($this->athleteUser)
        ->getJson(route('athlete.notifications.unread-count'))
        ->assertJson(['count' => 1]);
});
