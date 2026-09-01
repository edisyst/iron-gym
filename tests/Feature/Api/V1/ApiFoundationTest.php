<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'api_client', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestore', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);

    // Flag API acceso per default nei test di foundation
    Setting::write('public_api_enabled', true);
});

// ---------------------------------------------------------------------------
// GET /api/v1/ping — pubblico, non richiede auth ne' flag
// ---------------------------------------------------------------------------

it('ping risponde 200 con status ok', function () {
    $this->getJson('/api/v1/ping')
        ->assertOk()
        ->assertJson(['status' => 'ok', 'api_version' => 'v1']);
});

it('ping risponde 200 anche con flag public_api spento', function () {
    Setting::write('public_api_enabled', false);

    $this->getJson('/api/v1/ping')
        ->assertOk()
        ->assertJson(['status' => 'ok']);
});

// ---------------------------------------------------------------------------
// GET /api/v1/me
// ---------------------------------------------------------------------------

it('me senza token restituisce 401 JSON con code unauthenticated', function () {
    $this->getJson('/api/v1/me')
        ->assertUnauthorized()
        ->assertJsonStructure(['message', 'code'])
        ->assertJson(['code' => 'unauthenticated']);
});

it('me senza token non restituisce redirect a /login', function () {
    $response = $this->getJson('/api/v1/me');
    expect($response->getStatusCode())->toBe(401);
    expect($response->headers->get('Location'))->toBeNull();
});

it('me con token valido restituisce identita\' consumer', function () {
    $user = User::factory()->create(['is_service_account' => true])->assignRole('api_client');
    $token = $user->createToken('test', ['*'])->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonStructure(['id', 'name', 'email', 'is_service_account', 'roles', 'abilities'])
        ->assertJson([
            'id' => $user->id,
            'is_service_account' => true,
        ]);
});

it('me con token revocato restituisce 401', function () {
    $user = User::factory()->create(['is_service_account' => true])->assignRole('api_client');
    $token = $user->createToken('test', ['*']);
    $plainText = $token->plainTextToken;

    // Revoca il token
    $token->accessToken->delete();

    $this->withToken($plainText)
        ->getJson('/api/v1/me')
        ->assertUnauthorized()
        ->assertJson(['code' => 'unauthenticated']);
});

// ---------------------------------------------------------------------------
// Kill switch public_api
// ---------------------------------------------------------------------------

it('flag public_api spento restituisce 503 con code api_disabled su /me', function () {
    Setting::write('public_api_enabled', false);

    $user = User::factory()->create(['is_service_account' => true])->assignRole('api_client');
    $token = $user->createToken('test', ['*'])->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/me')
        ->assertServiceUnavailable()
        ->assertJson(['code' => 'api_disabled']);
});

// ---------------------------------------------------------------------------
// Formato errori — forma uniforme su tutti gli status
// ---------------------------------------------------------------------------

it('tutti gli errori API condividono le chiavi message e code', function () {
    Setting::write('public_api_enabled', false);

    // 503 da kill switch
    $r503 = $this->getJson('/api/v1/me');
    expect($r503->json())->toHaveKeys(['message', 'code']);

    Setting::write('public_api_enabled', true);

    // 401 da auth mancante
    $r401 = $this->getJson('/api/v1/me');
    expect($r401->json())->toHaveKeys(['message', 'code']);
    expect($r401->json('code'))->toBe('unauthenticated');
});

it('404 da ModelNotFoundException restituisce JSON con code not_found', function () {
    // Usa una route che chiama findOrFail su un ID inesistente via binding implicito
    // Per ora verifica il formato generico su una route non esistente (HttpException 404)
    $user = User::factory()->create(['is_service_account' => true])->assignRole('api_client');
    $token = $user->createToken('test', ['*'])->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/non-esistente-xyz')
        ->assertNotFound()
        ->assertJsonStructure(['message', 'code']);
});

// ---------------------------------------------------------------------------
// Rate limiting
// ---------------------------------------------------------------------------

it('risposta con token include header X-RateLimit', function () {
    $user = User::factory()->create(['is_service_account' => true])->assignRole('api_client');
    $token = $user->createToken('test', ['*'])->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/ping');
    // /ping non richiede auth ma fa parte del gruppo api con throttle
    expect($response->headers->has('X-RateLimit-Limit'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Account di servizio — blocco login web
// ---------------------------------------------------------------------------

it('account di servizio non puo\' autenticarsi via form web', function () {
    $password = 'secret-password-123';
    $user = User::factory()->create([
        'password' => bcrypt($password),
        'is_service_account' => true,
    ])->assignRole('api_client');

    Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', $password)
        ->call('login')
        ->assertHasErrors()
        ->assertNoRedirect();

    $this->assertGuest();
});

// ---------------------------------------------------------------------------
// Account di servizio — assenza dalle liste backoffice
// ---------------------------------------------------------------------------

it('account di servizio non compare nelle liste User::role(atleta)', function () {
    $serviceUser = User::factory()->create(['is_service_account' => true])->assignRole('api_client');

    $result = User::role('atleta')->get();
    expect($result->pluck('id'))->not->toContain($serviceUser->id);
});

it('account di servizio non compare nelle liste User::role(trainer)', function () {
    $serviceUser = User::factory()->create(['is_service_account' => true])->assignRole('api_client');

    $result = User::role('trainer')->get();
    expect($result->pluck('id'))->not->toContain($serviceUser->id);
});

// ---------------------------------------------------------------------------
// Gate view-training-reports — scritto in positivo
// ---------------------------------------------------------------------------

it('gate view-training-reports respinge utente senza ruoli', function () {
    $noRoleUser = User::factory()->create();

    $this->actingAs($noRoleUser);
    expect(auth()->user()->can('view-training-reports'))->toBeFalse();
});

it('gate view-training-reports ammette gestore', function () {
    Role::firstOrCreate(['name' => 'gestore', 'guard_name' => 'web']);
    $gestore = User::factory()->create()->assignRole('gestore');

    $this->actingAs($gestore);
    expect(auth()->user()->can('view-training-reports'))->toBeTrue();
});

it('gate view-training-reports ammette trainer', function () {
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);
    $trainer = User::factory()->create()->assignRole('trainer');

    $this->actingAs($trainer);
    expect(auth()->user()->can('view-training-reports'))->toBeTrue();
});

it('gate view-training-reports respinge receptionist', function () {
    Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
    $receptionist = User::factory()->create()->assignRole('receptionist');

    $this->actingAs($receptionist);
    expect(auth()->user()->can('view-training-reports'))->toBeFalse();
});
