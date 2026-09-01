<?php

use App\Models\ClassBooking;
use App\Models\ClassOccurrence;
use App\Models\Member;
use App\Models\PtBooking;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'api_client', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);

    Setting::write('public_api_enabled', true);
    Setting::write('group_classes_enabled', true);

    $this->serviceUser = User::factory()->create(['is_service_account' => true]);
    $this->serviceUser->assignRole('api_client');

    $this->readToken = $this->serviceUser->createToken('test-read', ['class-bookings:read'])->plainTextToken;
    $this->writeToken = $this->serviceUser->createToken('test-write', ['class-bookings:write'])->plainTextToken;

    $this->trainer = User::factory()->create()->assignRole('trainer');

    // Membro con abbonamento attivo e certificato valido
    $this->member = Member::factory()->create(['medical_cert_expiry' => now()->addYear()]);
    Subscription::factory()->create([
        'member_id' => $this->member->id,
        'started_at' => today()->subDays(10)->toDateString(),
        'expires_at' => today()->addDays(60)->toDateString(),
        'status' => 'active',
    ]);

    // Occorrenza futura nella finestra di prenotazione (3 giorni da oggi)
    $this->occurrence = ClassOccurrence::factory()->create([
        'trainer_id' => $this->trainer->id,
        'capacity' => 10,
        'date' => today()->addDays(3)->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'status' => 'planned',
    ]);
});

// ---------------------------------------------------------------------------
// GET /api/v1/class-bookings
// ---------------------------------------------------------------------------

it('class-bookings GET restituisce lista paginata con struttura corretta', function () {
    ClassBooking::factory()->create([
        'class_occurrence_id' => $this->occurrence->id,
        'member_id' => $this->member->id,
        'status' => 'confirmed',
    ]);

    $this->withToken($this->readToken)->getJson('/api/v1/class-bookings')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'class_occurrence_id', 'member_id', 'status', 'position', 'attended_at', 'created_at']],
            'links',
            'meta',
        ]);
});

it('class-bookings GET filtra per member_id', function () {
    $other = Member::factory()->create();

    ClassBooking::factory()->create([
        'class_occurrence_id' => $this->occurrence->id,
        'member_id' => $this->member->id,
        'status' => 'confirmed',
    ]);
    ClassBooking::factory()->create([
        'class_occurrence_id' => $this->occurrence->id,
        'member_id' => $other->id,
        'status' => 'confirmed',
    ]);

    $response = $this->withToken($this->readToken)
        ->getJson("/api/v1/class-bookings?member_id={$this->member->id}");

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.member_id'))->toBe($this->member->id);
});

it('class-bookings GET filtra per occurrence_id', function () {
    $occurrence2 = ClassOccurrence::factory()->create([
        'trainer_id' => $this->trainer->id,
        'status' => 'planned',
        'date' => today()->addDays(4)->toDateString(),
    ]);
    $other = Member::factory()->create();

    ClassBooking::factory()->create([
        'class_occurrence_id' => $this->occurrence->id,
        'member_id' => $this->member->id,
        'status' => 'confirmed',
    ]);
    ClassBooking::factory()->create([
        'class_occurrence_id' => $occurrence2->id,
        'member_id' => $other->id,
        'status' => 'confirmed',
    ]);

    $response = $this->withToken($this->readToken)
        ->getJson("/api/v1/class-bookings?occurrence_id={$this->occurrence->id}");

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.class_occurrence_id'))->toBe($this->occurrence->id);
});

it('class-bookings GET filtra per status', function () {
    ClassBooking::factory()->create([
        'class_occurrence_id' => $this->occurrence->id,
        'member_id' => $this->member->id,
        'status' => 'confirmed',
    ]);
    $other = Member::factory()->create();
    ClassBooking::factory()->create([
        'class_occurrence_id' => $this->occurrence->id,
        'member_id' => $other->id,
        'status' => 'waitlisted',
        'position' => 1,
    ]);

    $response = $this->withToken($this->readToken)
        ->getJson('/api/v1/class-bookings?status=waitlisted');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.status'))->toBe('waitlisted');
});

it('class-bookings GET senza token restituisce 401', function () {
    $this->getJson('/api/v1/class-bookings')
        ->assertUnauthorized()
        ->assertJson(['code' => 'unauthenticated']);
});

it('class-bookings GET con ability errata restituisce 403', function () {
    $this->withToken($this->writeToken)->getJson('/api/v1/class-bookings')
        ->assertForbidden();
});

it('class-bookings GET con flag public_api spento restituisce 503 api_disabled', function () {
    Setting::write('public_api_enabled', false);

    $this->withToken($this->readToken)->getJson('/api/v1/class-bookings')
        ->assertStatus(503)
        ->assertJson(['code' => 'api_disabled']);
});

it('class-bookings GET con flag group_classes spento restituisce 503 module_disabled', function () {
    Setting::write('group_classes_enabled', false);

    $this->withToken($this->readToken)->getJson('/api/v1/class-bookings')
        ->assertStatus(503)
        ->assertJson(['code' => 'module_disabled']);
});

// ---------------------------------------------------------------------------
// POST /api/v1/class-bookings
// ---------------------------------------------------------------------------

it('class-bookings POST crea iscrizione e restituisce 201 con Location', function () {
    $response = $this->withToken($this->writeToken)->postJson('/api/v1/class-bookings', [
        'member_id' => $this->member->id,
        'class_occurrence_id' => $this->occurrence->id,
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'class_occurrence_id', 'member_id', 'status', 'created_at']])
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.member_id', $this->member->id);

    expect($response->headers->get('Location'))->toContain('/api/v1/class-bookings/');
});

it('class-bookings POST mette in waitlist quando corso pieno', function () {
    $this->occurrence->update(['capacity' => 1]);

    $other = Member::factory()->create(['medical_cert_expiry' => now()->addYear()]);
    Subscription::factory()->create([
        'member_id' => $other->id,
        'started_at' => today()->subDays(5)->toDateString(),
        'expires_at' => today()->addDays(30)->toDateString(),
        'status' => 'active',
    ]);

    ClassBooking::factory()->create([
        'class_occurrence_id' => $this->occurrence->id,
        'member_id' => $other->id,
        'status' => 'confirmed',
    ]);

    $response = $this->withToken($this->writeToken)->postJson('/api/v1/class-bookings', [
        'member_id' => $this->member->id,
        'class_occurrence_id' => $this->occurrence->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'waitlisted');
});

it('class-bookings POST idempotente restituisce 200 se già iscritto', function () {
    ClassBooking::factory()->create([
        'class_occurrence_id' => $this->occurrence->id,
        'member_id' => $this->member->id,
        'status' => 'confirmed',
    ]);

    $response = $this->withToken($this->writeToken)->postJson('/api/v1/class-bookings', [
        'member_id' => $this->member->id,
        'class_occurrence_id' => $this->occurrence->id,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.status', 'confirmed');
});

it('class-bookings POST restituisce 404 se tesserato non trovato', function () {
    $this->withToken($this->writeToken)->postJson('/api/v1/class-bookings', [
        'member_id' => 99999,
        'class_occurrence_id' => $this->occurrence->id,
    ])
        ->assertNotFound()
        ->assertJson(['code' => 'not_found']);
});

it('class-bookings POST restituisce 404 se occorrenza non trovata', function () {
    $this->withToken($this->writeToken)->postJson('/api/v1/class-bookings', [
        'member_id' => $this->member->id,
        'class_occurrence_id' => 99999,
    ])
        ->assertNotFound()
        ->assertJson(['code' => 'not_found']);
});

it('class-bookings POST restituisce 422 booking_not_open se finestra non ancora aperta', function () {
    $occurrence = ClassOccurrence::factory()->create([
        'trainer_id' => $this->trainer->id,
        'capacity' => 10,
        'date' => today()->addDays(8)->toDateString(),
        'status' => 'planned',
    ]);

    $this->withToken($this->writeToken)->postJson('/api/v1/class-bookings', [
        'member_id' => $this->member->id,
        'class_occurrence_id' => $occurrence->id,
    ])
        ->assertUnprocessable()
        ->assertJson(['code' => 'booking_not_open']);
});

it('class-bookings POST restituisce 422 booking_closed se finestra chiusa', function () {
    $occurrence = ClassOccurrence::factory()->create([
        'trainer_id' => $this->trainer->id,
        'capacity' => 10,
        'date' => today()->toDateString(),
        'start_time' => now()->addMinutes(10)->format('H:i:s'),
        'end_time' => now()->addMinutes(70)->format('H:i:s'),
        'status' => 'planned',
    ]);

    $this->withToken($this->writeToken)->postJson('/api/v1/class-bookings', [
        'member_id' => $this->member->id,
        'class_occurrence_id' => $occurrence->id,
    ])
        ->assertUnprocessable()
        ->assertJson(['code' => 'booking_closed']);
});

it('class-bookings POST restituisce 422 occurrence_not_enrollable se occorrenza non planned', function () {
    $this->occurrence->update(['status' => 'cancelled']);

    $this->withToken($this->writeToken)->postJson('/api/v1/class-bookings', [
        'member_id' => $this->member->id,
        'class_occurrence_id' => $this->occurrence->id,
    ])
        ->assertUnprocessable()
        ->assertJson(['code' => 'occurrence_not_enrollable']);
});

it('class-bookings POST restituisce 422 subscription_inactive senza abbonamento', function () {
    $member = Member::factory()->create(['medical_cert_expiry' => now()->addYear()]);

    $this->withToken($this->writeToken)->postJson('/api/v1/class-bookings', [
        'member_id' => $member->id,
        'class_occurrence_id' => $this->occurrence->id,
    ])
        ->assertUnprocessable()
        ->assertJson(['code' => 'subscription_inactive']);
});

it('class-bookings POST restituisce 422 cert_invalid senza certificato valido', function () {
    $member = Member::factory()->create(['medical_cert_expiry' => now()->subDay()]);
    Subscription::factory()->create([
        'member_id' => $member->id,
        'started_at' => today()->subDays(5)->toDateString(),
        'expires_at' => today()->addDays(30)->toDateString(),
        'status' => 'active',
    ]);

    $this->withToken($this->writeToken)->postJson('/api/v1/class-bookings', [
        'member_id' => $member->id,
        'class_occurrence_id' => $this->occurrence->id,
    ])
        ->assertUnprocessable()
        ->assertJson(['code' => 'cert_invalid']);
});

it('class-bookings POST restituisce 409 athlete_overlap con corso sovrapposto', function () {
    $occurrence2 = ClassOccurrence::factory()->create([
        'trainer_id' => $this->trainer->id,
        'capacity' => 10,
        'date' => today()->addDays(3)->toDateString(),
        'start_time' => '10:30:00',
        'end_time' => '11:30:00',
        'status' => 'planned',
    ]);

    // Iscrive prima all'occurrence principale
    ClassBooking::factory()->create([
        'class_occurrence_id' => $this->occurrence->id,
        'member_id' => $this->member->id,
        'status' => 'confirmed',
    ]);

    $this->withToken($this->writeToken)->postJson('/api/v1/class-bookings', [
        'member_id' => $this->member->id,
        'class_occurrence_id' => $occurrence2->id,
    ])
        ->assertStatus(409)
        ->assertJson(['code' => 'athlete_overlap']);
});

it('class-bookings POST restituisce 409 pt_overlap con sessione PT sovrapposta', function () {
    PtBooking::factory()->create([
        'member_id' => $this->member->id,
        'trainer_id' => $this->trainer->id,
        'booked_date' => today()->addDays(3)->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'status' => 'confirmed',
    ]);

    $this->withToken($this->writeToken)->postJson('/api/v1/class-bookings', [
        'member_id' => $this->member->id,
        'class_occurrence_id' => $this->occurrence->id,
    ])
        ->assertStatus(409)
        ->assertJson(['code' => 'pt_overlap']);
});

it('class-bookings POST senza token restituisce 401', function () {
    $this->postJson('/api/v1/class-bookings', [
        'member_id' => $this->member->id,
        'class_occurrence_id' => $this->occurrence->id,
    ])
        ->assertUnauthorized()
        ->assertJson(['code' => 'unauthenticated']);
});

it('class-bookings POST con ability errata restituisce 403', function () {
    $this->withToken($this->readToken)->postJson('/api/v1/class-bookings', [
        'member_id' => $this->member->id,
        'class_occurrence_id' => $this->occurrence->id,
    ])
        ->assertForbidden();
});

it('class-bookings POST con flag group_classes spento restituisce 503 module_disabled', function () {
    Setting::write('group_classes_enabled', false);

    $this->withToken($this->writeToken)->postJson('/api/v1/class-bookings', [
        'member_id' => $this->member->id,
        'class_occurrence_id' => $this->occurrence->id,
    ])
        ->assertStatus(503)
        ->assertJson(['code' => 'module_disabled']);
});

it('class-bookings POST con flag public_api spento restituisce 503 api_disabled', function () {
    Setting::write('public_api_enabled', false);

    $this->withToken($this->writeToken)->postJson('/api/v1/class-bookings', [
        'member_id' => $this->member->id,
        'class_occurrence_id' => $this->occurrence->id,
    ])
        ->assertStatus(503)
        ->assertJson(['code' => 'api_disabled']);
});

// ---------------------------------------------------------------------------
// DELETE /api/v1/class-bookings/{booking}
// ---------------------------------------------------------------------------

it('class-bookings DELETE cancella iscrizione e restituisce 204', function () {
    $booking = ClassBooking::factory()->create([
        'class_occurrence_id' => $this->occurrence->id,
        'member_id' => $this->member->id,
        'status' => 'confirmed',
    ]);

    $this->withToken($this->writeToken)
        ->deleteJson("/api/v1/class-bookings/{$booking->id}")
        ->assertNoContent();

    expect($booking->fresh()->status)->toBe('cancelled_by_athlete');
});

it('class-bookings DELETE restituisce 409 cancel_deadline_exceeded oltre la finestra', function () {
    $occurrence = ClassOccurrence::factory()->create([
        'trainer_id' => $this->trainer->id,
        'capacity' => 10,
        'date' => today()->toDateString(),
        'start_time' => now()->addHours(2)->format('H:i:s'),
        'end_time' => now()->addHours(3)->format('H:i:s'),
        'status' => 'planned',
    ]);

    $booking = ClassBooking::factory()->create([
        'class_occurrence_id' => $occurrence->id,
        'member_id' => $this->member->id,
        'status' => 'confirmed',
    ]);

    $this->withToken($this->writeToken)
        ->deleteJson("/api/v1/class-bookings/{$booking->id}")
        ->assertStatus(409)
        ->assertJson(['code' => 'cancel_deadline_exceeded']);
});

it('class-bookings DELETE restituisce 409 booking_not_cancellable per stato non valido', function () {
    $booking = ClassBooking::factory()->create([
        'class_occurrence_id' => $this->occurrence->id,
        'member_id' => $this->member->id,
        'status' => 'no_show',
    ]);

    $this->withToken($this->writeToken)
        ->deleteJson("/api/v1/class-bookings/{$booking->id}")
        ->assertStatus(409)
        ->assertJson(['code' => 'booking_not_cancellable']);
});

it('class-bookings DELETE restituisce 404 per booking inesistente', function () {
    $this->withToken($this->writeToken)
        ->deleteJson('/api/v1/class-bookings/99999')
        ->assertNotFound();
});

it('class-bookings DELETE senza token restituisce 401', function () {
    $booking = ClassBooking::factory()->create([
        'class_occurrence_id' => $this->occurrence->id,
        'member_id' => $this->member->id,
        'status' => 'confirmed',
    ]);

    $this->deleteJson("/api/v1/class-bookings/{$booking->id}")
        ->assertUnauthorized()
        ->assertJson(['code' => 'unauthenticated']);
});

it('class-bookings DELETE con ability errata restituisce 403', function () {
    $booking = ClassBooking::factory()->create([
        'class_occurrence_id' => $this->occurrence->id,
        'member_id' => $this->member->id,
        'status' => 'confirmed',
    ]);

    $this->withToken($this->readToken)
        ->deleteJson("/api/v1/class-bookings/{$booking->id}")
        ->assertForbidden();
});

it('class-bookings DELETE con flag group_classes spento restituisce 503 module_disabled', function () {
    Setting::write('group_classes_enabled', false);

    $booking = ClassBooking::factory()->create([
        'class_occurrence_id' => $this->occurrence->id,
        'member_id' => $this->member->id,
        'status' => 'confirmed',
    ]);

    $this->withToken($this->writeToken)
        ->deleteJson("/api/v1/class-bookings/{$booking->id}")
        ->assertStatus(503)
        ->assertJson(['code' => 'module_disabled']);
});
