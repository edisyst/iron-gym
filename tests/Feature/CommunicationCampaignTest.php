<?php

use App\Jobs\SendCampaignMessages;
use App\Livewire\Backoffice\Communications\CommunicationCampaign;
use App\Models\Member;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'gestore', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);

    $this->gestore = User::factory()->create()->assignRole('gestore');

    $this->plan = SubscriptionPlan::create([
        'name' => 'Mensile',
        'price_cents' => 4000,
        'duration_days' => 30,
        'is_active' => true,
    ]);

    $athleteUser = User::factory()->create()->assignRole('atleta');
    $this->member = Member::factory()->create(['user_id' => $athleteUser->id]);
});

it('gestore può visualizzare il form campagna', function () {
    Livewire::actingAs($this->gestore)
        ->test(CommunicationCampaign::class)
        ->assertOk();
});

it('send() invia il job con i destinatari corretti', function () {
    Bus::fake();

    Livewire::actingAs($this->gestore)
        ->test(CommunicationCampaign::class)
        ->set('filter', 'all')
        ->set('channel', 'email')
        ->set('body', 'Messaggio di prova per test campagna comunicazione')
        ->call('send');

    Bus::assertDispatched(SendCampaignMessages::class, function (SendCampaignMessages $job) {
        return in_array($this->member->id, $job->memberIds);
    });
});

it('send() richiede body non vuoto', function () {
    Livewire::actingAs($this->gestore)
        ->test(CommunicationCampaign::class)
        ->set('body', '')
        ->call('send')
        ->assertHasErrors(['body']);
});

it('filtro active esclude tesserati senza abbonamento valido', function () {
    Bus::fake();

    // Tesserato senza abbonamento attivo — non deve ricevere la campagna
    $inactiveUser = User::factory()->create()->assignRole('atleta');
    Member::factory()->create(['user_id' => $inactiveUser->id]);

    // $this->member con abbonamento attivo
    Subscription::factory()->create([
        'member_id' => $this->member->id,
        'plan_id' => $this->plan->id,
        'status' => 'active',
        'started_at' => now()->subDays(5),
        'expires_at' => now()->addDays(25),
    ]);

    Livewire::actingAs($this->gestore)
        ->test(CommunicationCampaign::class)
        ->set('filter', 'active')
        ->set('channel', 'email')
        ->set('body', 'Solo per abbonati attivi — messaggio campagna')
        ->call('send');

    Bus::assertDispatched(SendCampaignMessages::class, function (SendCampaignMessages $job) {
        return count($job->memberIds) === 1 && $job->memberIds[0] === $this->member->id;
    });
});
