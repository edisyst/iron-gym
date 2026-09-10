<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'gestore', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
});

it('mostra il dropdown Amministrazione al gestore', function () {
    $u = User::factory()->create();
    $u->assignRole('gestore');

    $this->actingAs($u)->get('/backoffice/dashboard')
        ->assertOk()
        ->assertSee('Amministrazione')
        ->assertSee('backoffice/settings/artisan', false);
});

it('non mostra il dropdown Amministrazione al trainer', function () {
    $u = User::factory()->create();
    $u->assignRole('trainer');

    $this->actingAs($u)->get('/backoffice/dashboard')
        ->assertOk()
        ->assertDontSee('Amministrazione');
});

it('non mostra il dropdown Amministrazione al receptionist', function () {
    $u = User::factory()->create();
    $u->assignRole('receptionist');

    $this->actingAs($u)->get('/backoffice/dashboard')
        ->assertOk()
        ->assertDontSee('Amministrazione');
});
