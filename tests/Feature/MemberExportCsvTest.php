<?php

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'gestore', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);

    $this->gestore = User::factory()->create()->assignRole('gestore');
    $this->receptionist = User::factory()->create()->assignRole('receptionist');

    $this->member = Member::factory()->create([
        'last_name' => 'Colombo',
        'first_name' => 'Marta',
        'email' => 'marta@example.com',
        'is_active' => true,
    ]);
});

it('gestore scarica CSV tesserati', function () {
    $response = $this->actingAs($this->gestore)
        ->get(route('backoffice.members.export'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

it('CSV contiene cognome e nome del tesserato', function () {
    $content = $this->actingAs($this->gestore)
        ->get(route('backoffice.members.export'))
        ->streamedContent();

    expect($content)->toContain('Colombo')
        ->and($content)->toContain('Marta');
});

it('receptionist non può scaricare CSV tesserati', function () {
    $this->actingAs($this->receptionist)
        ->get(route('backoffice.members.export'))
        ->assertForbidden();
});

it('filtro certFilter=missing esclude tesserati con cert valido', function () {
    Member::factory()->create([
        'last_name' => 'ConCert',
        'medical_cert_expiry' => now()->addMonths(6)->toDateString(),
    ]);
    Member::factory()->create([
        'last_name' => 'SenzaCert',
        'medical_cert_expiry' => null,
    ]);

    $content = $this->actingAs($this->gestore)
        ->get(route('backoffice.members.export', ['certFilter' => 'missing']))
        ->streamedContent();

    expect($content)->toContain('SenzaCert')
        ->and($content)->not->toContain('ConCert');
});
