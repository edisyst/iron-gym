<?php

use App\Livewire\Athlete\Profile;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);

    $this->athleteUser = User::factory()->create()->assignRole('atleta');
    $this->trainer = User::factory()->create(['name' => 'Marco Rossi'])->assignRole('trainer');
});

it('tab messaggi mostra messaggio ricevuto con nome mittente', function () {
    Message::create([
        'sender_id' => $this->trainer->id,
        'receiver_id' => $this->athleteUser->id,
        'body' => 'Ottimo lavoro oggi!',
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'messaggi')
        ->assertSee('Marco Rossi')
        ->assertSee('Ottimo lavoro oggi!');
});

it('tab messaggi mostra messaggio inviato con prefisso Tu', function () {
    Message::create([
        'sender_id' => $this->athleteUser->id,
        'receiver_id' => $this->trainer->id,
        'body' => 'Grazie trainer!',
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'messaggi')
        ->assertSee('Tu →');
});

it('tab messaggi mostra badge non letti', function () {
    Message::create([
        'sender_id' => $this->trainer->id,
        'receiver_id' => $this->athleteUser->id,
        'body' => 'Messaggio non letto',
        'read_at' => null,
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->assertSee('1')
        ->set('activeSection', 'messaggi')
        ->assertSee('non letti');
});

it('tab messaggi mostra stato vuoto senza messaggi', function () {
    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'messaggi')
        ->assertSee('Nessun messaggio');
});

it('tab messaggi non mostra messaggi tra altri utenti', function () {
    $other = User::factory()->create()->assignRole('atleta');

    Message::create([
        'sender_id' => $other->id,
        'receiver_id' => $this->trainer->id,
        'body' => 'Messaggio privato altrui',
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Profile::class)
        ->set('activeSection', 'messaggi')
        ->assertDontSee('Messaggio privato altrui');
});
