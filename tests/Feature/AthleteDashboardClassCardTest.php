<?php

use App\Livewire\Athlete\Dashboard;
use App\Models\ClassBooking;
use App\Models\ClassOccurrence;
use App\Models\GroupClass;
use App\Models\Member;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'atleta', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);

    $this->athleteUser = User::factory()->create()->assignRole('atleta');
    $this->member = Member::factory()->create(['user_id' => $this->athleteUser->id]);
    $this->trainer = User::factory()->create()->assignRole('trainer');

    $this->groupClass = GroupClass::factory()->create(['name' => 'Yoga Flow']);

    $this->occurrence = ClassOccurrence::factory()->create([
        'group_class_id' => $this->groupClass->id,
        'trainer_id' => $this->trainer->id,
        'date' => now()->addDays(2)->toDateString(),
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
        'status' => 'planned',
    ]);
});

it('carica i corsi prenotati senza colonna status ambigua', function () {
    Setting::write('group_classes_enabled', true);
    Feature::purge('group_classes');

    ClassBooking::factory()->create([
        'class_occurrence_id' => $this->occurrence->id,
        'member_id' => $this->member->id,
        'status' => 'confirmed',
    ]);

    // La query fa join fra class_bookings e class_occurrences: entrambe hanno
    // una colonna status, quindi senza qualifica MySQL solleva errore 1052.
    Livewire::actingAs($this->athleteUser)
        ->test(Dashboard::class)
        ->assertOk()
        ->assertSee('Yoga Flow');
});

it('esclude le occorrenze cancellate e quelle passate', function () {
    Setting::write('group_classes_enabled', true);
    Feature::purge('group_classes');

    $this->occurrence->update(['status' => 'cancelled']);

    ClassBooking::factory()->create([
        'class_occurrence_id' => $this->occurrence->id,
        'member_id' => $this->member->id,
        'status' => 'confirmed',
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Dashboard::class)
        ->assertOk()
        ->assertDontSee('Yoga Flow');
});

it('non mostra la card corsi quando il flag e disattivo', function () {
    Setting::write('group_classes_enabled', false);
    Feature::purge('group_classes');

    ClassBooking::factory()->create([
        'class_occurrence_id' => $this->occurrence->id,
        'member_id' => $this->member->id,
        'status' => 'confirmed',
    ]);

    Livewire::actingAs($this->athleteUser)
        ->test(Dashboard::class)
        ->assertOk()
        ->assertDontSee('Yoga Flow');
});
