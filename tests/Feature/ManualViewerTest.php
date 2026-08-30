<?php

use App\Livewire\Backoffice\Settings\ManualViewer;
use App\Models\User;
use App\Services\ManualRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    $this->atleta = User::factory()->create()->assignRole('atleta');
});

it('gestore accede a /backoffice/settings e vede tab manuale', function () {
    $this->actingAs($this->gestore)
        ->get('/backoffice/settings')
        ->assertOk();
});

it('receptionist non accede a /backoffice/settings', function () {
    $this->actingAs($this->receptionist)
        ->get('/backoffice/settings')
        ->assertForbidden();
});

it('trainer non accede a /backoffice/settings', function () {
    $this->actingAs($this->trainer)
        ->get('/backoffice/settings')
        ->assertForbidden();
});

it('atleta non accede a /backoffice/settings', function () {
    $this->actingAs($this->atleta)
        ->get('/backoffice/settings')
        ->assertForbidden();
});

it('slug path-traversal ../etc/passwd restituisce 404', function () {
    $renderer = app(ManualRenderer::class);

    expect($renderer->slugExists('../etc/passwd'))->toBeFalse();
});

it('slug con slash /etc/passwd non esiste nel catalogo', function () {
    $renderer = app(ManualRenderer::class);

    expect($renderer->slugExists('/etc/passwd'))->toBeFalse();
});

it('slug inesistente restituisce false da slugExists', function () {
    $renderer = app(ManualRenderer::class);

    expect($renderer->slugExists('non-esiste-xyz'))->toBeFalse();
});

it('tutte le sezioni del manuale rendono senza eccezioni', function () {
    $renderer = app(ManualRenderer::class);
    $sections = $renderer->sections();

    expect($sections)->not->toBeEmpty();

    foreach ($sections as $slug => $section) {
        $html = $renderer->render($slug);
        expect($html)->toBeString()->not->toBeEmpty();
    }
});

it('ManualViewer monta con la prima sezione attiva', function () {
    Livewire::actingAs($this->gestore)
        ->test(ManualViewer::class)
        ->assertSet('currentSlug', fn (string $slug) => $slug !== '');
});

it('selectSection cambia sezione valida', function () {
    $renderer = app(ManualRenderer::class);
    $sections = $renderer->sections();
    $slugs = array_keys($sections);

    if (count($slugs) < 2) {
        $this->markTestSkipped('Meno di 2 sezioni disponibili.');
    }

    $second = $slugs[1];

    Livewire::actingAs($this->gestore)
        ->test(ManualViewer::class)
        ->call('selectSection', $second)
        ->assertSet('currentSlug', $second);
});

it('selectSection con slug inesistente non modifica currentSlug', function () {
    $component = Livewire::actingAs($this->gestore)
        ->test(ManualViewer::class);

    $initial = $component->get('currentSlug');

    $component->call('selectSection', 'slug-inesistente-xyz')
        ->assertSet('currentSlug', $initial);
});
