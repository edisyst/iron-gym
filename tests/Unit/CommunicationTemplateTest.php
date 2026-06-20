<?php

use App\Models\CommunicationTemplate;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('il template sostituisce correttamente le variabili con i dati del membro', function () {
    $member = Member::factory()->create([
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
        'medical_cert_expiry' => '2026-12-31',
    ]);

    $template = new CommunicationTemplate([
        'name' => 'Test',
        'channel' => 'email',
        'subject' => 'Ciao {{nome}} {{cognome}}',
        'body' => 'Il tuo cert scade il {{scadenza_certificato}}.',
    ]);

    $rendered = $template->render($member);

    expect($rendered['subject'])->toBe('Ciao Mario Rossi');
    expect($rendered['body'])->toBe('Il tuo cert scade il 31/12/2026.');
});

it('una variabile non riconosciuta viene lasciata intatta', function () {
    $member = Member::factory()->create([
        'first_name' => 'Luca',
        'last_name' => 'Bianchi',
    ]);

    $template = new CommunicationTemplate([
        'name' => 'Test',
        'channel' => 'email',
        'subject' => null,
        'body' => 'Ciao {{nome}}, la tua variabile {{sconosciuta}} rimane.',
    ]);

    $rendered = $template->render($member);

    expect($rendered['body'])->toContain('{{sconosciuta}}');
    expect($rendered['body'])->toContain('Luca');
});
