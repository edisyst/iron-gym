<?php

use App\Models\ClassBooking;
use App\Models\ClassOccurrence;
use App\Models\GroupClass;
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
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);

    Setting::write('public_api_enabled', true);
    Setting::write('group_classes_enabled', true);

    $this->serviceUser = User::factory()->create(['is_service_account' => true]);
    $this->serviceUser->assignRole('api_client');

    $this->token = $this->serviceUser->createToken('test', ['group-classes:read'])->plainTextToken;
});

// ---------------------------------------------------------------------------
// GET /api/v1/group-classes
// ---------------------------------------------------------------------------

it('group-classes restituisce lista paginata dei corsi attivi', function () {
    GroupClass::factory()->count(3)->create(['is_active' => true]);
    GroupClass::factory()->create(['is_active' => false]);

    $this->withToken($this->token)->getJson('/api/v1/group-classes')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'slug', 'name', 'duration_minutes', 'default_capacity', 'is_active']],
            'links',
            'meta',
        ])
        ->assertJsonCount(3, 'data');
});

it('group-classes con flag group_classes spento restituisce 503 module_disabled', function () {
    Setting::write('group_classes_enabled', false);

    $this->withToken($this->token)->getJson('/api/v1/group-classes')
        ->assertStatus(503)
        ->assertJson(['code' => 'module_disabled']);
});

it('group-classes senza token restituisce 401', function () {
    $this->getJson('/api/v1/group-classes')
        ->assertUnauthorized()
        ->assertJson(['code' => 'unauthenticated']);
});

it('group-classes con ability errata restituisce 403', function () {
    $token = $this->serviceUser->createToken('test', ['access-logs:read'])->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/group-classes')
        ->assertForbidden();
});

it('group-classes con flag public_api spento restituisce 503 api_disabled', function () {
    Setting::write('public_api_enabled', false);

    $this->withToken($this->token)->getJson('/api/v1/group-classes')
        ->assertStatus(503)
        ->assertJson(['code' => 'api_disabled']);
});

// ---------------------------------------------------------------------------
// GET /api/v1/class-occurrences
// ---------------------------------------------------------------------------

it('class-occurrences restituisce occorrenze future planned con struttura corretta', function () {
    $trainer = User::factory()->create()->assignRole('trainer');
    $class = GroupClass::factory()->create(['is_active' => true]);

    ClassOccurrence::factory()->create([
        'group_class_id' => $class->id,
        'trainer_id' => $trainer->id,
        'date' => today()->addDays(3)->toDateString(),
        'status' => 'planned',
        'capacity' => 10,
    ]);

    $this->withToken($this->token)->getJson('/api/v1/class-occurrences')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'group_class_id', 'group_class_name', 'date', 'start_time', 'end_time', 'capacity', 'available_spots', 'status', 'trainer_id', 'trainer_name']],
            'links',
            'meta',
        ]);
});

it('class-occurrences non mostra occorrenze cancellate o passate', function () {
    $class = GroupClass::factory()->create();

    ClassOccurrence::factory()->cancelled()->create(['group_class_id' => $class->id]);
    ClassOccurrence::factory()->past()->create(['group_class_id' => $class->id]);

    $response = $this->withToken($this->token)->getJson('/api/v1/class-occurrences');

    $response->assertOk();
    expect($response->json('data'))->toBeEmpty();
});

it('class-occurrences filtra per group_class_id', function () {
    $class1 = GroupClass::factory()->create();
    $class2 = GroupClass::factory()->create();

    ClassOccurrence::factory()->create([
        'group_class_id' => $class1->id,
        'date' => today()->addDays(2)->toDateString(),
        'status' => 'planned',
    ]);
    ClassOccurrence::factory()->create([
        'group_class_id' => $class2->id,
        'date' => today()->addDays(2)->toDateString(),
        'status' => 'planned',
    ]);

    $response = $this->withToken($this->token)
        ->getJson("/api/v1/class-occurrences?group_class_id={$class1->id}");

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.group_class_id'))->toBe($class1->id);
});

it('class-occurrences filtra per date_from e date_to', function () {
    $class = GroupClass::factory()->create();

    ClassOccurrence::factory()->create([
        'group_class_id' => $class->id,
        'date' => today()->addDays(2)->toDateString(),
        'status' => 'planned',
    ]);
    ClassOccurrence::factory()->create([
        'group_class_id' => $class->id,
        'date' => today()->addDays(20)->toDateString(),
        'status' => 'planned',
    ]);

    $from = today()->addDays(1)->toDateString();
    $to = today()->addDays(10)->toDateString();

    $response = $this->withToken($this->token)
        ->getJson("/api/v1/class-occurrences?date_from={$from}&date_to={$to}");

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

it('class-occurrences cap 31 giorni restituisce 422', function () {
    $from = today()->toDateString();
    $to = today()->addDays(32)->toDateString();

    $this->withToken($this->token)
        ->getJson("/api/v1/class-occurrences?date_from={$from}&date_to={$to}")
        ->assertStatus(422)
        ->assertJson(['code' => 'validation_failed']);
});

it('class-occurrences available_spots è corretto su occorrenza parzialmente prenotata', function () {
    $plan = SubscriptionPlan::factory()->create();
    $class = GroupClass::factory()->create();
    $occurrence = ClassOccurrence::factory()->create([
        'group_class_id' => $class->id,
        'date' => today()->addDays(2)->toDateString(),
        'status' => 'planned',
        'capacity' => 10,
    ]);

    $memberA = Member::factory()->create(['medical_cert_expiry' => now()->addYear()->toDateString()]);
    Subscription::factory()->create([
        'member_id' => $memberA->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'started_at' => today()->toDateString(),
        'expires_at' => today()->addMonth()->toDateString(),
    ]);

    ClassBooking::factory()->create([
        'class_occurrence_id' => $occurrence->id,
        'member_id' => $memberA->id,
        'status' => 'confirmed',
    ]);

    $response = $this->withToken($this->token)->getJson('/api/v1/class-occurrences');

    $response->assertOk();
    expect($response->json('data.0.available_spots'))->toBe(9)
        ->and($response->json('data.0.capacity'))->toBe(10);
});

it('class-occurrences non genera query N+1', function () {
    $class = GroupClass::factory()->create();

    ClassOccurrence::factory()->count(5)->create([
        'group_class_id' => $class->id,
        'date' => today()->addDays(2)->toDateString(),
        'status' => 'planned',
    ]);

    DB::enableQueryLog();
    $this->withToken($this->token)->getJson('/api/v1/class-occurrences')->assertOk();
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queryCount)->toBeLessThan(15);
});

it('class-occurrences con flag group_classes spento restituisce 503 module_disabled', function () {
    Setting::write('group_classes_enabled', false);

    $this->withToken($this->token)->getJson('/api/v1/class-occurrences')
        ->assertStatus(503)
        ->assertJson(['code' => 'module_disabled']);
});
