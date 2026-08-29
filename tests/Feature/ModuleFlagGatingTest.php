<?php

use App\Livewire\Athlete\Booking;
use App\Models\Member;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestore', 'guard_name' => 'web']);

    $this->athlete = User::factory()->create()->assignRole('atleta');
    $this->member = Member::factory()->create(['user_id' => $this->athlete->id]);
});

// ---------------------------------------------------------------------------
// messaging
// ---------------------------------------------------------------------------

it('messaging off: fetch unread-count assente nel layout atleta', function () {
    Setting::write('messaging_enabled', false);
    Feature::flushCache();

    $this->actingAs($this->athlete)
        ->get(route('athlete.dashboard'))
        ->assertOk()
        ->assertDontSee('messages-unread-count');
});

it('messaging off: link Messaggi assente dalla bottom-nav atleta', function () {
    Setting::write('messaging_enabled', false);
    Feature::flushCache();

    $this->actingAs($this->athlete)
        ->get(route('athlete.dashboard'))
        ->assertOk()
        ->assertDontSee(route('athlete.messages'));
});

// ---------------------------------------------------------------------------
// pt_bookings x group_classes — matrice 4 casi
// ---------------------------------------------------------------------------

it('pt_bookings off + group_classes off: route bookings restituisce 403', function () {
    Setting::write('pt_bookings_enabled', false);
    Setting::write('group_classes_enabled', false);
    Feature::flushCache();

    $this->actingAs($this->athlete)
        ->get(route('athlete.bookings'))
        ->assertForbidden();
});

it('pt_bookings on + group_classes off: pagina mostra tab PT, assente tab Corsi', function () {
    Setting::write('pt_bookings_enabled', true);
    Setting::write('group_classes_enabled', false);
    Feature::flushCache();

    $this->actingAs($this->athlete)
        ->get(route('athlete.bookings'))
        ->assertOk()
        ->assertSee('Sessione PT')
        ->assertDontSee('Corsi');
});

it('pt_bookings on + group_classes on: entrambi i tab visibili', function () {
    Setting::write('pt_bookings_enabled', true);
    Setting::write('group_classes_enabled', true);
    Feature::flushCache();

    $this->actingAs($this->athlete)
        ->get(route('athlete.bookings'))
        ->assertOk()
        ->assertSee('Sessione PT')
        ->assertSee('Corsi');
});

it('pt_bookings off + group_classes on: pagina mostra solo tab Corsi, PT assente', function () {
    Setting::write('pt_bookings_enabled', false);
    Setting::write('group_classes_enabled', true);
    Feature::flushCache();

    $this->actingAs($this->athlete)
        ->get(route('athlete.bookings'))
        ->assertOk()
        ->assertSee('Corsi')
        ->assertDontSee('Sessione PT');
});

it('pt_bookings off + group_classes off: link Prenota assente dalla bottom-nav', function () {
    Setting::write('pt_bookings_enabled', false);
    Setting::write('group_classes_enabled', false);
    Feature::flushCache();

    $this->actingAs($this->athlete)
        ->get(route('athlete.dashboard'))
        ->assertOk()
        ->assertDontSee(route('athlete.bookings'));
});

it('pt_bookings off: Booking mount forza activeTab a classes', function () {
    Setting::write('pt_bookings_enabled', false);
    Setting::write('group_classes_enabled', true);
    Feature::flushCache();

    Livewire::actingAs($this->athlete)
        ->test(Booking::class)
        ->assertSet('activeTab', 'classes');
});
