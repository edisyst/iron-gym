<?php

use App\Livewire\Backoffice\Settings\FeatureFlagManager;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'gestore', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
});

// --- Accesso sezione Impostazioni ---

it('gestore accede a /backoffice/settings con 200', function () {
    $gestore = User::factory()->create()->assignRole('gestore');

    $this->actingAs($gestore)
        ->get('/backoffice/settings')
        ->assertOk();
});

it('trainer rifiutato da /backoffice/settings con 403', function () {
    $trainer = User::factory()->create()->assignRole('trainer');

    $this->actingAs($trainer)
        ->get('/backoffice/settings')
        ->assertForbidden();
});

it('receptionist rifiutato da /backoffice/settings con 403', function () {
    $receptionist = User::factory()->create()->assignRole('receptionist');

    $this->actingAs($receptionist)
        ->get('/backoffice/settings')
        ->assertForbidden();
});

it('atleta rifiutato da /backoffice/settings con 403', function () {
    $atleta = User::factory()->create()->assignRole('atleta');

    $this->actingAs($atleta)
        ->get('/backoffice/settings')
        ->assertForbidden();
});

// --- Redirect 301 dalla vecchia route ---

it('la vecchia route admin/feature-flags redirige con 301', function () {
    $gestore = User::factory()->create()->assignRole('gestore');

    $this->actingAs($gestore)
        ->get('/backoffice/admin/feature-flags')
        ->assertRedirect('/backoffice/settings/feature-flags')
        ->assertStatus(301);
});

// --- DIFETTO-A: pattern uniforme per tutti i flag ---

it('spegnere periodization_engine fa risolvere false a utenti mai risolti', function () {
    $gestore = User::factory()->create()->assignRole('gestore');
    $trainer = User::factory()->create()->assignRole('trainer');

    // Accende l'interruttore
    Setting::write('periodization_engine_enabled', true);
    Feature::purge('periodization_engine');
    Feature::flushCache();

    expect(Feature::for($gestore)->active('periodization_engine'))->toBeTrue();

    // Spegne tramite toggle
    Livewire::actingAs($gestore)
        ->test(FeatureFlagManager::class)
        ->call('requestToggle', 'periodization_engine', false)
        ->call('confirmToggle');

    Feature::flushCache();

    // Utente che non aveva mai risolto il flag
    $fresh = User::factory()->create()->assignRole('gestore');
    expect(Feature::for($fresh)->active('periodization_engine'))->toBeFalse()
        ->and(Feature::for($gestore)->active('periodization_engine'))->toBeFalse()
        ->and(Feature::for($trainer)->active('periodization_engine'))->toBeFalse();
});

it('spegnere financial_reports fa risolvere false a utenti mai risolti', function () {
    $gestore = User::factory()->create()->assignRole('gestore');

    Setting::write('financial_reports_enabled', true);
    Feature::purge('financial_reports');
    Feature::flushCache();

    expect(Feature::for($gestore)->active('financial_reports'))->toBeTrue();

    Livewire::actingAs($gestore)
        ->test(FeatureFlagManager::class)
        ->call('requestToggle', 'financial_reports', false)
        ->call('confirmToggle');

    Feature::flushCache();

    $fresh = User::factory()->create()->assignRole('gestore');
    expect(Feature::for($fresh)->active('financial_reports'))->toBeFalse()
        ->and(Feature::for($gestore)->active('financial_reports'))->toBeFalse();
});

it('spegnere push_notifications fa risolvere false a utenti mai risolti', function () {
    $gestore = User::factory()->create()->assignRole('gestore');
    $atleta = User::factory()->create()->assignRole('atleta');

    Setting::write('push_notifications_enabled', true);
    Feature::purge('push_notifications');
    Feature::flushCache();

    expect(Feature::for($atleta)->active('push_notifications'))->toBeTrue();

    Livewire::actingAs($gestore)
        ->test(FeatureFlagManager::class)
        ->call('requestToggle', 'push_notifications', false)
        ->call('confirmToggle');

    Feature::flushCache();

    $fresh = User::factory()->create()->assignRole('atleta');
    expect(Feature::for($fresh)->active('push_notifications'))->toBeFalse()
        ->and(Feature::for($atleta)->active('push_notifications'))->toBeFalse();
});

it('spegnere group_classes fa risolvere false a utenti mai risolti', function () {
    $gestore = User::factory()->create()->assignRole('gestore');

    Setting::write('group_classes_enabled', true);
    Feature::purge('group_classes');
    Feature::flushCache();

    expect(Feature::active('group_classes'))->toBeTrue();

    Livewire::actingAs($gestore)
        ->test(FeatureFlagManager::class)
        ->call('requestToggle', 'group_classes', false)
        ->call('confirmToggle');

    Feature::flushCache();
    Feature::purge('group_classes');

    expect(Feature::active('group_classes'))->toBeFalse();
});

// --- DIFETTO-B: UI mostra stato interruttore, non stato risolto per gestore ---

it('la UI mostra push_notifications acceso anche se il gestore lo risolve false', function () {
    $gestore = User::factory()->create()->assignRole('gestore');

    // Interruttore acceso
    Setting::write('push_notifications_enabled', true);

    // Il definer risolve false per il gestore (ruolo non coperto dallo scope)
    Feature::purge('push_notifications');
    Feature::flushCache();
    expect(Feature::for($gestore)->active('push_notifications'))->toBeFalse();

    // La UI deve mostrare Attivo perche' legge Setting, non Feature::active
    $component = Livewire::actingAs($gestore)
        ->test(FeatureFlagManager::class);

    expect($component->get('confirmActive'))->toBeFalse();

    $statuses = $component->viewData('statuses');
    expect($statuses['push_notifications'])->toBeTrue();
});
