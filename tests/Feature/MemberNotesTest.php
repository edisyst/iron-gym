<?php

use App\Livewire\Backoffice\Members\MemberForm;
use App\Livewire\Backoffice\Members\MemberList;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'gestore', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);

    $this->gestore = User::factory()->create()->assignRole('gestore');
});

it('form salva note interne nel tesserato', function () {
    $member = Member::factory()->create(['notes' => null]);

    Livewire::actingAs($this->gestore)
        ->test(MemberForm::class, ['member' => $member])
        ->set('notes', 'Attenzione: ginocchio destro problematico.')
        ->call('save');

    expect($member->fresh()->notes)->toBe('Attenzione: ginocchio destro problematico.');
});

it('lista mostra icona nota quando tesserato ha note', function () {
    Member::factory()->create([
        'last_name' => 'Ferri',
        'first_name' => 'Anna',
        'notes' => 'Ipertensione lieve.',
    ]);

    Livewire::actingAs($this->gestore)
        ->test(MemberList::class)
        ->assertSee('fa-sticky-note', false);
});

it('lista non mostra icona nota quando tesserato non ha note', function () {
    Member::factory()->create([
        'last_name' => 'Rossi',
        'first_name' => 'Luca',
        'notes' => null,
    ]);

    Livewire::actingAs($this->gestore)
        ->test(MemberList::class)
        ->assertDontSee('fa-sticky-note', false);
});

it('form pre-carica note esistenti in modifica', function () {
    $member = Member::factory()->create(['notes' => 'Nota preesistente.']);

    $component = Livewire::actingAs($this->gestore)
        ->test(MemberForm::class, ['member' => $member]);

    expect($component->get('notes'))->toBe('Nota preesistente.');
});
