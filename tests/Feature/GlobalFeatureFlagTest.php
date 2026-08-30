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

    config(['features.group_classes_enabled' => false]);
});

it('usa il default da config quando settings non ha la chiave', function () {
    $user = User::factory()->create()->assignRole('atleta');

    expect(Feature::for($user)->active('group_classes'))->toBeFalse();

    config(['features.group_classes_enabled' => true]);
    Feature::flushCache();
    Feature::purge('group_classes');

    expect(Feature::for($user)->active('group_classes'))->toBeTrue();
});

it('applica il flag globale anche a utenti che non lo hanno mai risolto', function () {
    $existing = User::factory()->create()->assignRole('atleta');

    // Risolve il flag: Pennant memorizza il valore per questo scope
    expect(Feature::for($existing)->active('group_classes'))->toBeFalse();

    Setting::write('group_classes_enabled', true);
    Feature::purge('group_classes');
    Feature::flushCache();

    // Utente che non aveva alcuna riga memorizzata
    $fresh = User::factory()->create()->assignRole('trainer');

    expect(Feature::for($existing)->active('group_classes'))->toBeTrue()
        ->and(Feature::for($fresh)->active('group_classes'))->toBeTrue();
});

it('il toggle del gestore ha effetto su tutti gli utenti', function () {
    $gestore = User::factory()->create()->assignRole('gestore');
    $atleta = User::factory()->create()->assignRole('atleta');

    expect(Feature::for($atleta)->active('group_classes'))->toBeFalse();

    Livewire::actingAs($gestore)
        ->test(FeatureFlagManager::class)
        ->call('requestToggle', 'group_classes', true)
        ->call('confirmToggle');

    Feature::flushCache();

    expect(Setting::bool('group_classes_enabled'))->toBeTrue()
        ->and(Feature::for($atleta)->active('group_classes'))->toBeTrue();

    // e si puo' rispegnere
    Livewire::actingAs($gestore)
        ->test(FeatureFlagManager::class)
        ->call('requestToggle', 'group_classes', false)
        ->call('confirmToggle');

    Feature::flushCache();

    expect(Feature::for($atleta)->active('group_classes'))->toBeFalse();
});

it('nega il toggle a chi non e gestore', function () {
    $trainer = User::factory()->create()->assignRole('trainer');

    Livewire::actingAs($trainer)
        ->test(FeatureFlagManager::class)
        ->call('requestToggle', 'group_classes', true)
        ->call('confirmToggle')
        ->assertForbidden();
});

it('rifiuta flag non presenti nella lista gestita', function () {
    $gestore = User::factory()->create()->assignRole('gestore');

    Livewire::actingAs($gestore)
        ->test(FeatureFlagManager::class)
        ->call('requestToggle', 'flag_inventato', true)
        ->call('confirmToggle')
        ->assertForbidden();
});

it('Setting legge e scrive valori booleani con cache invalidata', function () {
    expect(Setting::bool('chiave_assente', true))->toBeTrue();

    Setting::write('chiave_assente', false);
    expect(Setting::bool('chiave_assente', true))->toBeFalse();

    Setting::write('chiave_assente', true);
    expect(Setting::bool('chiave_assente', false))->toBeTrue();
});
