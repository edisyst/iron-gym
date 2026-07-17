<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('la pagina profilo atleta renderizza senza errori', function () {
    $athlete = User::factory()->create();
    $athlete->assignRole('atleta');

    $response = $this->actingAs($athlete)->get(route('athlete.profile'));

    $response->assertOk();
});

it('il layout atleta contiene il toggle tema con aria-pressed', function () {
    $athlete = User::factory()->create();
    $athlete->assignRole('atleta');

    $response = $this->actingAs($athlete)->get(route('athlete.profile'));

    $response->assertOk();
    $response->assertSee('ig-theme-toggle', false);
    $response->assertSee('aria-pressed', false);
});

it('il layout atleta contiene script anti-FOUC prima del CSS', function () {
    $athlete = User::factory()->create();
    $athlete->assignRole('atleta');

    $response = $this->actingAs($athlete)->get(route('athlete.profile'));

    $html = $response->getContent();

    $posScript = strpos($html, 'ig-theme');
    $posCss = strpos($html, 'athlete.css');

    expect($posScript)->toBeLessThan($posCss);
});

it('il toggle viewport e visibile in ambiente local', function () {
    $athlete = User::factory()->create();
    $athlete->assignRole('atleta');

    $this->app->detectEnvironment(fn () => 'local');

    $response = $this->actingAs($athlete)->get(route('athlete.profile'));

    $response->assertOk();
    $response->assertSee('ig-devtools-card', false);
    $response->assertSee('ig-viewport-toggle', false);
});

it('il toggle viewport e assente in ambiente production', function () {
    $athlete = User::factory()->create();
    $athlete->assignRole('atleta');

    $this->app->detectEnvironment(fn () => 'production');

    $response = $this->actingAs($athlete)->get(route('athlete.profile'));

    $response->assertOk();
    $response->assertDontSee('ig-devtools-card', false);
});
