<?php

use App\Models\AccessLog;
use App\Models\Exercise;
use App\Models\Member;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'api_client', 'guard_name' => 'web']);

    Setting::write('public_api_enabled', true);

    $this->serviceUser = User::factory()->create([
        'is_service_account' => true,
    ]);
    $this->serviceUser->assignRole('api_client');
});

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

function tokenWith(User $user, array $abilities): string
{
    return $user->createToken('test', $abilities)->plainTextToken;
}

// ---------------------------------------------------------------------------
// Kill switch — tutti e 7 gli endpoint restituiscono 503 con flag spento
// ---------------------------------------------------------------------------

it('kill switch 503 su subscription-plans', function () {
    Setting::write('public_api_enabled', false);
    $token = tokenWith($this->serviceUser, ['subscription-plans:read']);

    $this->withToken($token)->getJson('/api/v1/subscription-plans')
        ->assertStatus(503)
        ->assertJsonFragment(['code' => 'api_disabled']);
});

it('kill switch 503 su members', function () {
    Setting::write('public_api_enabled', false);
    $token = tokenWith($this->serviceUser, ['members:read']);

    $this->withToken($token)->getJson('/api/v1/members')
        ->assertStatus(503);
});

it('kill switch 503 su exercises', function () {
    Setting::write('public_api_enabled', false);
    $token = tokenWith($this->serviceUser, ['exercises:read']);

    $this->withToken($token)->getJson('/api/v1/exercises')
        ->assertStatus(503);
});

it('kill switch 503 su access-logs', function () {
    Setting::write('public_api_enabled', false);
    $token = tokenWith($this->serviceUser, ['access-logs:read']);

    $this->withToken($token)->getJson('/api/v1/access-logs')
        ->assertStatus(503);
});

// ---------------------------------------------------------------------------
// 401 senza token — campione su 4 endpoint
// ---------------------------------------------------------------------------

it('401 senza token su subscription-plans', function () {
    $this->getJson('/api/v1/subscription-plans')
        ->assertUnauthorized()
        ->assertJsonFragment(['code' => 'unauthenticated']);
});

it('401 senza token su members', function () {
    $this->getJson('/api/v1/members')
        ->assertUnauthorized();
});

it('401 senza token su access-logs', function () {
    $this->getJson('/api/v1/access-logs')
        ->assertUnauthorized();
});

it('401 senza token su exercises', function () {
    $this->getJson('/api/v1/exercises')
        ->assertUnauthorized();
});

// ---------------------------------------------------------------------------
// 403 con token privo dell'ability richiesta
// ---------------------------------------------------------------------------

it('403 su subscription-plans con ability sbagliata', function () {
    $token = tokenWith($this->serviceUser, ['members:read']);

    $this->withToken($token)->getJson('/api/v1/subscription-plans')
        ->assertForbidden();
});

it('403 su members con ability sbagliata', function () {
    $token = tokenWith($this->serviceUser, ['exercises:read']);

    $this->withToken($token)->getJson('/api/v1/members')
        ->assertForbidden();
});

it('403 su access-logs con ability sbagliata', function () {
    $token = tokenWith($this->serviceUser, ['members:read']);

    $this->withToken($token)->getJson('/api/v1/access-logs')
        ->assertForbidden();
});

it('403 su exercises con ability sbagliata', function () {
    $token = tokenWith($this->serviceUser, ['members:read']);

    $this->withToken($token)->getJson('/api/v1/exercises')
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// GET /api/v1/subscription-plans
// ---------------------------------------------------------------------------

it('subscription-plans restituisce lista paginata', function () {
    SubscriptionPlan::factory()->count(3)->create();
    $token = tokenWith($this->serviceUser, ['subscription-plans:read']);

    $response = $this->withToken($token)->getJson('/api/v1/subscription-plans');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'name', 'price_cents', 'duration_days', 'is_active']],
            'links',
            'meta',
        ]);
});

it('subscription-plans filtra per active=true', function () {
    SubscriptionPlan::factory()->create(['is_active' => true, 'name' => 'Attivo']);
    SubscriptionPlan::factory()->create(['is_active' => false, 'name' => 'Inattivo']);
    $token = tokenWith($this->serviceUser, ['subscription-plans:read']);

    $response = $this->withToken($token)->getJson('/api/v1/subscription-plans?active=true&per_page=100');

    $response->assertOk();
    $data = $response->json('data');
    expect($data)->toHaveCount(1)
        ->and($data[0]['name'])->toBe('Attivo');
});

it('subscription-plans rispetta per_page', function () {
    SubscriptionPlan::factory()->count(5)->create();
    $token = tokenWith($this->serviceUser, ['subscription-plans:read']);

    $response = $this->withToken($token)->getJson('/api/v1/subscription-plans?per_page=2');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
    expect($response->json('meta.per_page'))->toBe(2);
});

it('subscription-plans 422 se per_page supera 100', function () {
    $token = tokenWith($this->serviceUser, ['subscription-plans:read']);

    $this->withToken($token)->getJson('/api/v1/subscription-plans?per_page=101')
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => 'validation_error']);
});

// ---------------------------------------------------------------------------
// GET /api/v1/members
// ---------------------------------------------------------------------------

it('members restituisce lista paginata', function () {
    Member::factory()->count(3)->create();
    $token = tokenWith($this->serviceUser, ['members:read']);

    $response = $this->withToken($token)->getJson('/api/v1/members');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'first_name', 'last_name', 'email', 'is_active', 'medical_cert_valid']],
            'links',
            'meta',
        ]);
});

it('members non espone medical_cert_expiry senza ability medical-read', function () {
    Member::factory()->create();
    $token = tokenWith($this->serviceUser, ['members:read']);

    $response = $this->withToken($token)->getJson('/api/v1/members');

    $response->assertOk();
    $item = $response->json('data.0');
    expect($item)->not->toHaveKey('medical_cert_expiry');
});

it('members espone medical_cert_expiry con ability medical-read', function () {
    Member::factory()->create(['medical_cert_expiry' => '2027-01-01']);
    $token = tokenWith($this->serviceUser, ['members:read', 'members:medical-read']);

    $response = $this->withToken($token)->getJson('/api/v1/members');

    $response->assertOk();
    $item = $response->json('data.0');
    expect($item)->toHaveKey('medical_cert_expiry');
});

it('members filtra per ricerca testuale', function () {
    Member::factory()->create(['last_name' => 'Rossi', 'first_name' => 'Mario']);
    Member::factory()->create(['last_name' => 'Bianchi', 'first_name' => 'Luca']);
    $token = tokenWith($this->serviceUser, ['members:read']);

    $response = $this->withToken($token)->getJson('/api/v1/members?search=Rossi');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.last_name'))->toBe('Rossi');
});

it('members 403 su filtro cert_expiry_before senza ability medical-read', function () {
    $token = tokenWith($this->serviceUser, ['members:read']);

    $this->withToken($token)->getJson('/api/v1/members?cert_expiry_before=2027-01-01')
        ->assertUnprocessable();
});

it('members non espone soft-deleted', function () {
    $member = Member::factory()->create();
    $member->delete();
    $token = tokenWith($this->serviceUser, ['members:read']);

    $response = $this->withToken($token)->getJson('/api/v1/members');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(0);
});

// ---------------------------------------------------------------------------
// GET /api/v1/members/{id}
// ---------------------------------------------------------------------------

it('members show restituisce dettaglio', function () {
    $member = Member::factory()->create();
    $token = tokenWith($this->serviceUser, ['members:read']);

    $this->withToken($token)->getJson("/api/v1/members/{$member->id}")
        ->assertOk()
        ->assertJsonFragment(['id' => $member->id]);
});

it('members show 404 per membro inesistente', function () {
    $token = tokenWith($this->serviceUser, ['members:read']);

    $this->withToken($token)->getJson('/api/v1/members/99999')
        ->assertNotFound()
        ->assertJsonFragment(['code' => 'not_found']);
});

it('members show non espone medical_cert_expiry senza medical-read', function () {
    $member = Member::factory()->create();
    $token = tokenWith($this->serviceUser, ['members:read']);

    $response = $this->withToken($token)->getJson("/api/v1/members/{$member->id}");

    expect($response->json())->not->toHaveKey('medical_cert_expiry');
});

// ---------------------------------------------------------------------------
// GET /api/v1/members/{id}/subscription
// ---------------------------------------------------------------------------

it('member subscription restituisce abbonamento attivo', function () {
    $plan = SubscriptionPlan::factory()->create();
    $member = Member::factory()->create();
    Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'started_at' => now()->subDays(5)->format('Y-m-d'),
        'expires_at' => now()->addDays(25)->format('Y-m-d'),
    ]);
    $token = tokenWith($this->serviceUser, ['members:read']);

    $this->withToken($token)->getJson("/api/v1/members/{$member->id}/subscription")
        ->assertOk()
        ->assertJsonStructure(['plan_id', 'plan_name', 'status', 'started_at', 'expires_at']);
});

it('member subscription 404 senza abbonamento attivo', function () {
    $member = Member::factory()->create();
    $token = tokenWith($this->serviceUser, ['members:read']);

    $this->withToken($token)->getJson("/api/v1/members/{$member->id}/subscription")
        ->assertNotFound()
        ->assertJsonFragment(['code' => 'not_found']);
});

// ---------------------------------------------------------------------------
// GET /api/v1/access-logs
// ---------------------------------------------------------------------------

it('access-logs restituisce lista paginata', function () {
    $member = Member::factory()->create();
    AccessLog::create(['member_id' => $member->id, 'checked_in_at' => now()]);
    $token = tokenWith($this->serviceUser, ['access-logs:read']);

    $response = $this->withToken($token)->getJson('/api/v1/access-logs');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'member_id', 'checked_in_at']],
        ]);
});

it('access-logs filtra per member_id', function () {
    $m1 = Member::factory()->create();
    $m2 = Member::factory()->create();
    AccessLog::create(['member_id' => $m1->id, 'checked_in_at' => now()]);
    AccessLog::create(['member_id' => $m2->id, 'checked_in_at' => now()]);
    $token = tokenWith($this->serviceUser, ['access-logs:read']);

    $response = $this->withToken($token)->getJson("/api/v1/access-logs?member_id={$m1->id}");

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.member_id'))->toBe($m1->id);
});

it('access-logs 422 se range date supera 31 giorni', function () {
    $token = tokenWith($this->serviceUser, ['access-logs:read']);

    $this->withToken($token)->getJson('/api/v1/access-logs?date_from=2026-01-01&date_to=2026-03-01')
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => 'validation_error']);
});

it('access-logs N+1: max 2 query per lista', function () {
    $m = Member::factory()->create();
    AccessLog::create(['member_id' => $m->id, 'checked_in_at' => now()]);
    AccessLog::create(['member_id' => $m->id, 'checked_in_at' => now()->subHour()]);
    $token = tokenWith($this->serviceUser, ['access-logs:read']);

    $queryCount = 0;
    DB::listen(function () use (&$queryCount): void {
        $queryCount++;
    });

    $this->withToken($token)->getJson('/api/v1/access-logs')->assertOk();

    expect($queryCount)->toBeLessThanOrEqual(4);
});

// ---------------------------------------------------------------------------
// GET /api/v1/exercises  e  GET /api/v1/exercises/{slug}
// ---------------------------------------------------------------------------

it('exercises restituisce lista paginata', function () {
    Exercise::factory()->count(3)->create();
    $token = tokenWith($this->serviceUser, ['exercises:read']);

    $response = $this->withToken($token)->getJson('/api/v1/exercises');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'slug', 'name', 'measurement_type', 'mechanic']],
            'links',
            'meta',
        ]);
});

it('exercises filtra per mechanic', function () {
    Exercise::factory()->create(['mechanic' => 'compound']);
    Exercise::factory()->create(['mechanic' => 'isolation']);
    $token = tokenWith($this->serviceUser, ['exercises:read']);

    $response = $this->withToken($token)->getJson('/api/v1/exercises?mechanic=compound');

    $response->assertOk();
    foreach ($response->json('data') as $item) {
        expect($item['mechanic'])->toBe('compound');
    }
});

it('exercises show restituisce dettaglio per slug', function () {
    $exercise = Exercise::factory()->create(['slug' => 'squat']);
    $token = tokenWith($this->serviceUser, ['exercises:read']);

    $this->withToken($token)->getJson('/api/v1/exercises/squat')
        ->assertOk()
        ->assertJsonFragment(['slug' => 'squat']);
});

it('exercises show 404 per slug inesistente', function () {
    $token = tokenWith($this->serviceUser, ['exercises:read']);

    $this->withToken($token)->getJson('/api/v1/exercises/esercizio-inesistente')
        ->assertNotFound()
        ->assertJsonFragment(['code' => 'not_found']);
});

it('exercises non espone soft-deleted', function () {
    $exercise = Exercise::factory()->create();
    $exercise->delete();
    $token = tokenWith($this->serviceUser, ['exercises:read']);

    $response = $this->withToken($token)->getJson('/api/v1/exercises');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(0);
});

it('exercises N+1: max 4 query per lista con muscles', function () {
    Exercise::factory()->count(3)->create();
    $token = tokenWith($this->serviceUser, ['exercises:read']);

    $queryCount = 0;
    DB::listen(function () use (&$queryCount): void {
        $queryCount++;
    });

    $this->withToken($token)->getJson('/api/v1/exercises')->assertOk();

    expect($queryCount)->toBeLessThanOrEqual(5);
});

// ---------------------------------------------------------------------------
// api:issue-token whitelist
// ---------------------------------------------------------------------------

it('api:issue-token rifiuta ability non in whitelist', function () {
    $this->artisan('api:create-service-account', ['consumer' => 'test-client'])->assertSuccessful();

    $this->artisan('api:issue-token', [
        'consumer' => 'test-client',
        '--abilities' => 'ruolo_inventato:write',
    ])->assertFailed();
});

it('api:issue-token accetta tutte le abilities della whitelist', function () {
    $this->artisan('api:create-service-account', ['consumer' => 'test-whitelist'])->assertSuccessful();

    $abilities = 'subscription-plans:read,members:read,members:medical-read,access-logs:read,exercises:read';

    $this->artisan('api:issue-token', [
        'consumer' => 'test-whitelist',
        '--abilities' => $abilities,
    ])->assertSuccessful();
});
