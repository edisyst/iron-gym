<?php

/**
 * Test per i gap del piano di test funzionale R09+.
 *
 * Copre i casi non già verificati dalla suite esistente:
 * - TC-OPH-001..007: OpeningHoursManager (slot ricorrenti + eccezioni + permessi)
 * - TC-CLS-018: atleta non accede a /backoffice/group-classes
 * - TC-CLS-019: receptionist non può cancellare un corso (deleteClass)
 * - TC-NOT-006: utente backoffice non accede a /athlete/notifications
 */

use App\Livewire\Backoffice\Calendar\GroupClassManager;
use App\Livewire\Backoffice\Settings\OpeningHoursManager;
use App\Models\ClassOccurrence;
use App\Models\GroupClass;
use App\Models\OpeningHour;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestore', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);

    $this->gestore = User::factory()->create()->assignRole('gestore');
    $this->trainer = User::factory()->create()->assignRole('trainer');
    $this->receptionist = User::factory()->create()->assignRole('receptionist');
    $this->atleta = User::factory()->create()->assignRole('atleta');
});

// ---------------------------------------------------------------------------
// TC-OPH-001 — Visualizzazione slot ricorrenti
// ---------------------------------------------------------------------------

it('TC-OPH-001: gestore visualizza gli slot ricorrenti', function () {
    OpeningHour::create([
        'day_of_week' => 0,
        'specific_date' => null,
        'is_annual' => false,
        'start_time' => '09:00',
        'end_time' => '21:00',
        'is_open' => true,
    ]);

    Livewire::actingAs($this->gestore)
        ->test(OpeningHoursManager::class)
        ->assertSee('09:00');
});

// ---------------------------------------------------------------------------
// TC-OPH-002 — Aggiunta slot ricorrente (happy path)
// ---------------------------------------------------------------------------

it('TC-OPH-002: gestore aggiunge slot ricorrente', function () {
    Livewire::actingAs($this->gestore)
        ->test(OpeningHoursManager::class)
        ->set('newDayOfWeek', 1)
        ->set('newStartTime', '08:00')
        ->set('newEndTime', '20:00')
        ->call('addSlot')
        ->assertHasNoErrors();

    expect(OpeningHour::where('day_of_week', 1)->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
// TC-OPH-003 — Modifica slot ricorrente inline
// ---------------------------------------------------------------------------

it('TC-OPH-003: gestore modifica slot ricorrente inline', function () {
    $slot = OpeningHour::create([
        'day_of_week' => 2,
        'specific_date' => null,
        'is_annual' => false,
        'start_time' => '09:00',
        'end_time' => '21:00',
        'is_open' => true,
    ]);

    Livewire::actingAs($this->gestore)
        ->test(OpeningHoursManager::class)
        ->call('startEditSlot', $slot->id)
        ->set('editSlotStart', '10:00')
        ->set('editSlotEnd', '22:00')
        ->call('saveSlot')
        ->assertHasNoErrors();

    expect(OpeningHour::find($slot->id)->start_time)->toStartWith('10:00');
});

// ---------------------------------------------------------------------------
// TC-OPH-004 — Eliminazione slot ricorrente
// ---------------------------------------------------------------------------

it('TC-OPH-004: gestore elimina slot ricorrente', function () {
    $slot = OpeningHour::create([
        'day_of_week' => 3,
        'specific_date' => null,
        'is_annual' => false,
        'start_time' => '09:00',
        'end_time' => '21:00',
        'is_open' => true,
    ]);

    Livewire::actingAs($this->gestore)
        ->test(OpeningHoursManager::class)
        ->call('deleteSlot', $slot->id);

    expect(OpeningHour::find($slot->id))->toBeNull();
});

// ---------------------------------------------------------------------------
// TC-OPH-005 — Aggiunta eccezione (giorno chiuso)
// ---------------------------------------------------------------------------

it('TC-OPH-005: gestore aggiunge eccezione giorno chiuso', function () {
    Livewire::actingAs($this->gestore)
        ->test(OpeningHoursManager::class)
        ->set('newDate', '2026-12-25')
        ->set('newIsOpen', false)
        ->set('newNotes', 'Natale')
        ->call('addOverride')
        ->assertHasNoErrors();

    expect(OpeningHour::where('is_open', false)->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
// TC-OPH-006 — Validazione: end_time deve essere > start_time
// ---------------------------------------------------------------------------

it('TC-OPH-006: addSlot fallisce se end_time <= start_time', function () {
    Livewire::actingAs($this->gestore)
        ->test(OpeningHoursManager::class)
        ->set('newDayOfWeek', 0)
        ->set('newStartTime', '20:00')
        ->set('newEndTime', '09:00')
        ->call('addSlot')
        ->assertHasErrors(['newEndTime']);
});

// ---------------------------------------------------------------------------
// TC-OPH-007 — Permesso negato: atleta non accede agli orari
// ---------------------------------------------------------------------------

it('TC-OPH-007: atleta non accede a /backoffice/settings/opening-hours', function () {
    $this->actingAs($this->atleta)
        ->get('/backoffice/settings/opening-hours')
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// TC-CLS-018 — Permesso negato: atleta non accede a /backoffice/group-classes
// ---------------------------------------------------------------------------

it('TC-CLS-018: atleta non accede a /backoffice/group-classes', function () {
    Setting::write('group_classes_enabled', true);
    Feature::flushCache();

    $this->actingAs($this->atleta)
        ->get('/backoffice/group-classes')
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// TC-CLS-019 — Permesso negato: receptionist non cancella corso
// ---------------------------------------------------------------------------

it('TC-CLS-019: receptionist non può cancellare un corso (deleteClass → 403)', function () {
    Setting::write('group_classes_enabled', true);
    Feature::flushCache();

    $gc = GroupClass::create([
        'name' => 'Yoga',
        'slug' => 'yoga',
        'description' => 'Test',
        'duration_minutes' => 60,
        'default_capacity' => 10,
        'is_active' => true,
    ]);

    $occurrence = ClassOccurrence::create([
        'group_class_id' => $gc->id,
        'trainer_id' => $this->trainer->id,
        'date' => Carbon::today()->addDays(3)->toDateString(),
        'start_time' => '18:00:00',
        'end_time' => '19:00:00',
        'capacity' => 10,
        'status' => 'planned',
    ]);

    Livewire::actingAs($this->receptionist)
        ->test(GroupClassManager::class)
        ->call('deleteClass', $occurrence->id)
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// TC-NOT-006 — Permesso negato: utente backoffice non accede a /athlete/notifications
// ---------------------------------------------------------------------------

it('TC-NOT-006: gestore non accede a /athlete/notifications (route richiede role:atleta)', function () {
    $this->actingAs($this->gestore)
        ->get('/athlete/notifications')
        ->assertForbidden();
});

it('TC-NOT-006: trainer non accede a /athlete/notifications', function () {
    $this->actingAs($this->trainer)
        ->get('/athlete/notifications')
        ->assertForbidden();
});
