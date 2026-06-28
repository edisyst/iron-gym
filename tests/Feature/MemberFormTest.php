<?php

use App\Livewire\Backoffice\Members\MemberForm;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->gestore = User::factory()->create();
    $this->gestore->assignRole('gestore');

    $this->trainer = User::factory()->create();
    $this->trainer->assignRole('trainer');

    $this->receptionist = User::factory()->create();
    $this->receptionist->assignRole('receptionist');
});

// --- CREATE senza account ---

it('il gestore crea un tesserato senza account', function () {
    Livewire::actingAs($this->gestore)
        ->test(MemberForm::class)
        ->set('first_name', 'Mario')
        ->set('last_name', 'Verdi')
        ->set('email', 'mario.verdi@example.com')
        ->set('medical_cert_expiry', '2027-12-31')
        ->call('save')
        ->assertRedirect(route('backoffice.members.index'));

    $this->assertDatabaseHas('members', [
        'first_name' => 'Mario',
        'last_name' => 'Verdi',
        'email' => 'mario.verdi@example.com',
    ]);

    expect(Member::where('email', 'mario.verdi@example.com')->first()->user_id)->toBeNull();
});

// --- CREATE con account atleta ---

it('il gestore crea un tesserato con account atleta', function () {
    Livewire::actingAs($this->gestore)
        ->test(MemberForm::class)
        ->set('first_name', 'Marco')
        ->set('last_name', 'Rossi')
        ->set('email', 'marco.rossi@example.com')
        ->set('medical_cert_expiry', '2027-06-30')
        ->set('create_account', true)
        ->set('account_password', 'password123')
        ->call('save')
        ->assertRedirect(route('backoffice.members.index'));

    $member = Member::where('email', 'marco.rossi@example.com')->firstOrFail();
    expect($member->user_id)->toBeInt();

    $user = User::findOrFail($member->user_id);
    expect($user->email)->toBe('marco.rossi@example.com')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->hasRole('atleta'))->toBeTrue();
});

it('account creato ha email_verified_at impostato', function () {
    Livewire::actingAs($this->gestore)
        ->test(MemberForm::class)
        ->set('first_name', 'Anna')
        ->set('last_name', 'Bianchi')
        ->set('email', 'anna.bianchi@example.com')
        ->set('create_account', true)
        ->set('account_password', 'securepass')
        ->call('save');

    $user = User::where('email', 'anna.bianchi@example.com')->firstOrFail();
    expect($user->email_verified_at)->not->toBeNull();
});

it('create_account false non crea User', function () {
    Livewire::actingAs($this->gestore)
        ->test(MemberForm::class)
        ->set('first_name', 'Luigi')
        ->set('last_name', 'Neri')
        ->set('email', 'luigi.neri@example.com')
        ->set('create_account', false)
        ->call('save');

    expect(User::where('email', 'luigi.neri@example.com')->exists())->toBeFalse();
});

// --- VALIDAZIONE ---

it('create_account true richiede password minimo 8 caratteri', function () {
    Livewire::actingAs($this->gestore)
        ->test(MemberForm::class)
        ->set('first_name', 'Test')
        ->set('last_name', 'User')
        ->set('email', 'test@example.com')
        ->set('create_account', true)
        ->set('account_password', 'short')
        ->call('save')
        ->assertHasErrors(['account_password']);
});

it('email duplicata in members viene rifiutata', function () {
    Member::factory()->create(['email' => 'esistente@example.com']);

    Livewire::actingAs($this->gestore)
        ->test(MemberForm::class)
        ->set('first_name', 'Nuovo')
        ->set('last_name', 'Utente')
        ->set('email', 'esistente@example.com')
        ->call('save')
        ->assertHasErrors(['email']);
});

it('first_name e last_name sono obbligatori', function () {
    Livewire::actingAs($this->gestore)
        ->test(MemberForm::class)
        ->set('email', 'test@example.com')
        ->call('save')
        ->assertHasErrors(['first_name', 'last_name']);
});

// --- UPDATE ---

it('il trainer può aggiornare un tesserato', function () {
    $member = Member::factory()->create(['email' => 'originale@example.com']);

    Livewire::actingAs($this->trainer)
        ->test(MemberForm::class, ['member' => $member])
        ->set('city', 'Roma')
        ->call('save')
        ->assertRedirect(route('backoffice.members.index'));

    expect($member->fresh()->city)->toBe('Roma');
});

it('il receptionist non può aggiornare un tesserato', function () {
    $member = Member::factory()->create();

    Livewire::actingAs($this->receptionist)
        ->test(MemberForm::class, ['member' => $member])
        ->set('city', 'Milano')
        ->call('save')
        ->assertForbidden();
});

it('update non crea account anche se create_account era true', function () {
    $member = Member::factory()->create(['email' => 'update@example.com']);
    $userCountBefore = User::count();

    Livewire::actingAs($this->gestore)
        ->test(MemberForm::class, ['member' => $member])
        ->set('create_account', true)
        ->set('account_password', 'password123')
        ->set('city', 'Napoli')
        ->call('save');

    expect(User::count())->toBe($userCountBefore);
});
