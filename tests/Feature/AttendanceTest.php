<?php

use App\Livewire\Backoffice\Calendar\GroupClassManager;
use App\Models\ClassOccurrence;
use App\Models\Member;
use App\Models\Subscription;
use App\Models\User;
use App\Services\ClassBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'gestore', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);

    $this->gestore = User::factory()->create()->assignRole('gestore');
    $this->trainer = User::factory()->create()->assignRole('trainer');

    $this->occurrence = ClassOccurrence::factory()->create([
        'trainer_id' => $this->trainer->id,
        'capacity' => 10,
        'date' => today()->subDay()->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'status' => 'planned',
    ]);

    // Due membri con abbonamento e cert valido
    $this->member1 = Member::factory()->create(['medical_cert_expiry' => now()->addYear()]);
    Subscription::factory()->create([
        'member_id' => $this->member1->id,
        'started_at' => today()->subDays(10)->toDateString(),
        'expires_at' => today()->addDays(30)->toDateString(),
        'status' => 'active',
    ]);

    $this->member2 = Member::factory()->create(['medical_cert_expiry' => now()->addYear()]);
    Subscription::factory()->create([
        'member_id' => $this->member2->id,
        'started_at' => today()->subDays(10)->toDateString(),
        'expires_at' => today()->addDays(30)->toDateString(),
        'status' => 'active',
    ]);

    $svc = app(ClassBookingService::class);
    $this->booking1 = $svc->enroll($this->occurrence, $this->member1);
    $this->booking2 = $svc->enroll($this->occurrence, $this->member2);
});

it('completeOccurrence transiziona stato a completed e registra presenze', function () {
    Livewire::actingAs($this->gestore)
        ->test(GroupClassManager::class)
        ->call('completeOccurrence', $this->occurrence->id);

    $this->occurrence->refresh();
    expect($this->occurrence->status)->toBe('completed');
    expect($this->booking1->fresh()->attended_at)->not->toBeNull();
    expect($this->booking2->fresh()->attended_at)->not->toBeNull();
});

it('completeOccurrence non agisce su occorrenze già completate', function () {
    $this->occurrence->update(['status' => 'completed']);

    Livewire::actingAs($this->gestore)
        ->test(GroupClassManager::class)
        ->call('completeOccurrence', $this->occurrence->id);

    // Status rimane completed ma non viene rieseguito bulk update
    expect($this->occurrence->fresh()->status)->toBe('completed');
});

it('markNoShow segna un iscritto come assente', function () {
    Livewire::actingAs($this->gestore)
        ->test(GroupClassManager::class)
        ->call('markNoShow', $this->booking1->id);

    $b = $this->booking1->fresh();
    expect($b->status)->toBe('no_show');
    expect($b->attended_at)->toBeNull();
});

it('markAttended ripristina un no-show a presente', function () {
    $this->booking1->update(['status' => 'no_show', 'attended_at' => null]);

    Livewire::actingAs($this->gestore)
        ->test(GroupClassManager::class)
        ->call('markAttended', $this->booking1->id);

    $b = $this->booking1->fresh();
    expect($b->status)->toBe('confirmed');
    expect($b->attended_at)->not->toBeNull();
});

it('markNoShow poi completeOccurrence registra presenti solo i confermati', function () {
    // Segna booking1 come no_show prima del completamento
    Livewire::actingAs($this->gestore)
        ->test(GroupClassManager::class)
        ->call('markNoShow', $this->booking1->id);

    Livewire::actingAs($this->gestore)
        ->test(GroupClassManager::class)
        ->call('completeOccurrence', $this->occurrence->id);

    // booking1 era no_show → non deve ricevere attended_at
    expect($this->booking1->fresh()->attended_at)->toBeNull();
    // booking2 era confirmed → deve ricevere attended_at
    expect($this->booking2->fresh()->attended_at)->not->toBeNull();
});

it('trainer può completare un corso', function () {
    Livewire::actingAs($this->trainer)
        ->test(GroupClassManager::class)
        ->call('completeOccurrence', $this->occurrence->id);

    expect($this->occurrence->fresh()->status)->toBe('completed');
});
