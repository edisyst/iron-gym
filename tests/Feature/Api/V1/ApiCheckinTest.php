<?php

use App\Models\AccessLog;
use App\Models\Member;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'api_client', 'guard_name' => 'web']);

    Setting::write('public_api_enabled', true);

    $this->serviceUser = User::factory()->create(['is_service_account' => true]);
    $this->serviceUser->assignRole('api_client');

    $this->token = $this->serviceUser->createToken('test', ['access-logs:write'])->plainTextToken;

    $this->plan = SubscriptionPlan::factory()->create();
});

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

function checkinMember(array $memberOverrides = [], array $subOverrides = []): Member
{
    $member = Member::factory()->create(array_merge([
        'medical_cert_expiry' => now()->addYear()->toDateString(),
    ], $memberOverrides));

    if (! array_key_exists('skip_sub', $subOverrides)) {
        Subscription::factory()->create(array_merge([
            'member_id' => $member->id,
            'plan_id' => SubscriptionPlan::factory()->create()->id,
            'status' => 'active',
            'started_at' => today()->toDateString(),
            'expires_at' => today()->addMonth()->toDateString(),
            'accesses_remaining' => null,
            'accesses_used' => 0,
        ], $subOverrides));
    }

    return $member;
}

// ---------------------------------------------------------------------------
// 1. Happy path — 201 con Location e struttura risorsa
// ---------------------------------------------------------------------------

it('check-in corretto restituisce 201 con Location e risorsa', function () {
    $member = checkinMember();

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/access-logs', ['member_id' => $member->id]);

    $response->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'member_id', 'member_name', 'subscription_id', 'checked_in_at', 'note']]);

    $logId = $response->json('data.id');
    expect($response->headers->get('Location'))->toBe('/api/v1/access-logs/'.$logId);

    $this->assertDatabaseHas('access_logs', [
        'member_id' => $member->id,
        'checked_in_by' => $this->serviceUser->id,
    ]);
});

it('checked_in_by contiene l\'id dell\'account di servizio', function () {
    $member = checkinMember();

    $this->withToken($this->token)
        ->postJson('/api/v1/access-logs', ['member_id' => $member->id]);

    $log = AccessLog::where('member_id', $member->id)->first();
    expect($log->checked_in_by)->toBe($this->serviceUser->id);
});

// ---------------------------------------------------------------------------
// 2. Fallimenti di dominio — 422 con code distinto
// ---------------------------------------------------------------------------

it('certificato scaduto restituisce 422 con code cert_invalid', function () {
    $member = checkinMember(memberOverrides: [
        'medical_cert_expiry' => now()->subDay()->toDateString(),
    ]);

    $this->withToken($this->token)
        ->postJson('/api/v1/access-logs', ['member_id' => $member->id])
        ->assertStatus(422)
        ->assertJson(['code' => 'cert_invalid']);

    $this->assertDatabaseMissing('access_logs', ['member_id' => $member->id]);
});

it('abbonamento non attivo restituisce 422 con code subscription_inactive', function () {
    $member = checkinMember(subOverrides: ['skip_sub' => true]);

    $this->withToken($this->token)
        ->postJson('/api/v1/access-logs', ['member_id' => $member->id])
        ->assertStatus(422)
        ->assertJson(['code' => 'subscription_inactive']);
});

it('accessi esauriti restituisce 422 con code accesses_exhausted', function () {
    $member = checkinMember(subOverrides: ['accesses_remaining' => 0]);

    $this->withToken($this->token)
        ->postJson('/api/v1/access-logs', ['member_id' => $member->id])
        ->assertStatus(422)
        ->assertJson(['code' => 'accesses_exhausted']);

    $this->assertDatabaseMissing('access_logs', ['member_id' => $member->id]);
});

// ---------------------------------------------------------------------------
// 3. 404 — tesserato assente o soft-deleted
// ---------------------------------------------------------------------------

it('tesserato inesistente restituisce 404', function () {
    $this->withToken($this->token)
        ->postJson('/api/v1/access-logs', ['member_id' => 99999])
        ->assertNotFound()
        ->assertJson(['code' => 'not_found']);
});

it('tesserato soft-deleted restituisce 404', function () {
    $member = checkinMember();
    $member->delete();

    $this->withToken($this->token)
        ->postJson('/api/v1/access-logs', ['member_id' => $member->id])
        ->assertNotFound()
        ->assertJson(['code' => 'not_found']);
});

// ---------------------------------------------------------------------------
// 4. Ability mancante — 403
// ---------------------------------------------------------------------------

it('token senza access-logs:write restituisce 403', function () {
    $token = $this->serviceUser->createToken('test', ['access-logs:read'])->plainTextToken;

    $member = checkinMember();

    $this->withToken($token)
        ->postJson('/api/v1/access-logs', ['member_id' => $member->id])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// 5. Kill switch — 503
// ---------------------------------------------------------------------------

it('flag public_api spento restituisce 503', function () {
    Setting::write('public_api_enabled', false);
    $member = checkinMember();

    $this->withToken($this->token)
        ->postJson('/api/v1/access-logs', ['member_id' => $member->id])
        ->assertStatus(503)
        ->assertJson(['code' => 'api_disabled']);
});

// ---------------------------------------------------------------------------
// 6. Idempotenza — secondo invio entro finestra
// ---------------------------------------------------------------------------

it('secondo check-in entro finestra restituisce 200 con log esistente', function () {
    $member = checkinMember();

    $first = $this->withToken($this->token)
        ->postJson('/api/v1/access-logs', ['member_id' => $member->id]);
    $first->assertCreated();
    $firstId = $first->json('data.id');

    $second = $this->withToken($this->token)
        ->postJson('/api/v1/access-logs', ['member_id' => $member->id]);
    $second->assertOk();
    $second->assertJson(['data' => ['id' => $firstId]]);

    expect(AccessLog::where('member_id', $member->id)->count())->toBe(1);
});

it('duplicato entro finestra non decrementa accesses_remaining una seconda volta', function () {
    $member = checkinMember(subOverrides: ['accesses_remaining' => 5, 'accesses_used' => 0]);
    $sub = Subscription::where('member_id', $member->id)->first();

    $this->withToken($this->token)
        ->postJson('/api/v1/access-logs', ['member_id' => $member->id])
        ->assertCreated();

    $this->withToken($this->token)
        ->postJson('/api/v1/access-logs', ['member_id' => $member->id])
        ->assertOk();

    expect($sub->fresh()->accesses_remaining)->toBe(4)
        ->and($sub->fresh()->accesses_used)->toBe(1);
});

// ---------------------------------------------------------------------------
// 7. Validazione payload
// ---------------------------------------------------------------------------

it('member_id assente restituisce 422 validation_failed', function () {
    $this->withToken($this->token)
        ->postJson('/api/v1/access-logs', [])
        ->assertStatus(422)
        ->assertJson(['code' => 'validation_failed'])
        ->assertJsonStructure(['errors' => ['member_id']]);
});
