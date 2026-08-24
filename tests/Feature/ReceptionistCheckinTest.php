<?php

use App\Livewire\Athlete\Dashboard as AthleteDashboard;
use App\Livewire\Backoffice\Access\AccessLogList;
use App\Livewire\Backoffice\Admin\PlateInventoryManager;
use App\Livewire\Backoffice\Calendar\BookingList;
use App\Livewire\Backoffice\Calendar\GroupClassManager;
use App\Livewire\Backoffice\Calendar\TrainerCalendar;
use App\Livewire\Backoffice\Exercises\ExerciseList;
use App\Livewire\Backoffice\Members\MemberList;
use App\Livewire\Backoffice\Mesocycles\MesocycleList;
use App\Livewire\Backoffice\Templates\TemplateList;
use App\Models\ClassBooking;
use App\Models\ClassOccurrence;
use App\Models\GroupClass;
use App\Models\Member;
use App\Models\PtBooking;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Setup comune
// ---------------------------------------------------------------------------

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->gestore = User::factory()->create();
    $this->gestore->assignRole('gestore');

    $this->trainer = User::factory()->create();
    $this->trainer->assignRole('trainer');

    $this->receptionist = User::factory()->create();
    $this->receptionist->assignRole('receptionist');

    $this->plan = SubscriptionPlan::factory()->create([
        'max_accesses' => null,
    ]);

    // Tesserato con cert. valido e abbonamento attivo
    $this->memberOk = Member::factory()->create([
        'medical_cert_expiry' => now()->addYear()->toDateString(),
    ]);
    Subscription::factory()->create([
        'member_id' => $this->memberOk->id,
        'plan_id' => $this->plan->id,
        'started_at' => today()->toDateString(),
        'expires_at' => today()->addMonth()->toDateString(),
        'status' => 'active',
    ]);

    // Tesserato con cert. scaduto
    $this->memberExpiredCert = Member::factory()->create([
        'medical_cert_expiry' => now()->subDay()->toDateString(),
    ]);
    Subscription::factory()->create([
        'member_id' => $this->memberExpiredCert->id,
        'plan_id' => $this->plan->id,
        'started_at' => today()->toDateString(),
        'expires_at' => today()->addMonth()->toDateString(),
        'status' => 'active',
    ]);

    // Tesserato senza cert.
    $this->memberNoCert = Member::factory()->create([
        'medical_cert_expiry' => null,
    ]);
    Subscription::factory()->create([
        'member_id' => $this->memberNoCert->id,
        'plan_id' => $this->plan->id,
        'started_at' => today()->toDateString(),
        'expires_at' => today()->addMonth()->toDateString(),
        'status' => 'active',
    ]);

    // Tesserato con cert. valido ma senza abbonamento attivo
    $this->memberNoSub = Member::factory()->create([
        'medical_cert_expiry' => now()->addYear()->toDateString(),
    ]);

    // Tesserato con cert. valido, abbonamento con 0 accessi residui
    $this->memberNoAccesses = Member::factory()->create([
        'medical_cert_expiry' => now()->addYear()->toDateString(),
    ]);
    Subscription::factory()->create([
        'member_id' => $this->memberNoAccesses->id,
        'plan_id' => $this->plan->id,
        'started_at' => today()->toDateString(),
        'expires_at' => today()->addMonth()->toDateString(),
        'status' => 'active',
        'accesses_remaining' => 0,
    ]);
});

// ---------------------------------------------------------------------------
// 1. Check-in — flusso positivo
// ---------------------------------------------------------------------------

it('receptionist registra accesso per tesserato con cert. valido e abbonamento attivo', function () {
    Livewire::actingAs($this->receptionist)
        ->test(AccessLogList::class)
        ->call('selectMember', $this->memberOk->id)
        ->call('registerAccess')
        ->assertHasNoErrors()
        ->assertSet('showModal', false)
        ->assertSet('checkinError', '');

    $this->assertDatabaseHas('access_logs', [
        'member_id' => $this->memberOk->id,
        'checked_in_by' => $this->receptionist->id,
    ]);
});

it('il check-in incrementa accesses_used sull\'abbonamento', function () {
    $sub = Subscription::where('member_id', $this->memberOk->id)->first();
    expect($sub->accesses_used)->toBe(0);

    Livewire::actingAs($this->receptionist)
        ->test(AccessLogList::class)
        ->call('selectMember', $this->memberOk->id)
        ->call('registerAccess');

    expect($sub->fresh()->accesses_used)->toBe(1);
});

// ---------------------------------------------------------------------------
// 2. Check-in — certificato medico
// ---------------------------------------------------------------------------

it('check-in bloccato se certificato medico è scaduto', function () {
    Livewire::actingAs($this->receptionist)
        ->test(AccessLogList::class)
        ->call('openModal')
        ->call('selectMember', $this->memberExpiredCert->id)
        ->call('registerAccess')
        ->assertSet('showModal', true)
        ->assertSet('checkinError', 'Certificato medico scaduto o mancante. Rinnovarlo prima di accedere alla struttura.');

    $this->assertDatabaseMissing('access_logs', [
        'member_id' => $this->memberExpiredCert->id,
    ]);
});

it('check-in bloccato se certificato medico è assente', function () {
    Livewire::actingAs($this->receptionist)
        ->test(AccessLogList::class)
        ->call('openModal')
        ->call('selectMember', $this->memberNoCert->id)
        ->call('registerAccess')
        ->assertSet('showModal', true)
        ->assertSet('checkinError', 'Certificato medico scaduto o mancante. Rinnovarlo prima di accedere alla struttura.');

    $this->assertDatabaseMissing('access_logs', [
        'member_id' => $this->memberNoCert->id,
    ]);
});

// ---------------------------------------------------------------------------
// 3. Check-in — abbonamento
// ---------------------------------------------------------------------------

it('check-in bloccato se il tesserato non ha abbonamento attivo', function () {
    Livewire::actingAs($this->receptionist)
        ->test(AccessLogList::class)
        ->call('selectMember', $this->memberNoSub->id)
        ->call('registerAccess')
        ->assertSet('checkinError', 'Il tesserato non ha un abbonamento attivo.');

    $this->assertDatabaseMissing('access_logs', [
        'member_id' => $this->memberNoSub->id,
    ]);
});

it('check-in bloccato se gli accessi residui sono esauriti', function () {
    Livewire::actingAs($this->receptionist)
        ->test(AccessLogList::class)
        ->call('selectMember', $this->memberNoAccesses->id)
        ->call('registerAccess')
        ->assertSet('checkinError', 'Il tesserato ha esaurito gli accessi disponibili.');

    $this->assertDatabaseMissing('access_logs', [
        'member_id' => $this->memberNoAccesses->id,
    ]);
});

it('check-in bloccato se nessun tesserato selezionato', function () {
    Livewire::actingAs($this->receptionist)
        ->test(AccessLogList::class)
        ->call('registerAccess')
        ->assertSet('checkinError', 'Seleziona un tesserato.');
});

// ---------------------------------------------------------------------------
// 4. Autorizzazione route — receptionist riceve 403
// ---------------------------------------------------------------------------

it('receptionist riceve 403 su /backoffice/communications/campaign', function () {
    $this->actingAs($this->receptionist)
        ->get(route('backoffice.communications.campaign'))
        ->assertForbidden();
});

it('receptionist riceve 403 su /backoffice/calendar/availability', function () {
    $this->actingAs($this->receptionist)
        ->get(route('backoffice.calendar.availability'))
        ->assertForbidden();
});

it('trainer accede a /backoffice/calendar/availability', function () {
    $this->actingAs($this->trainer)
        ->get(route('backoffice.calendar.availability'))
        ->assertOk();
});

// ---------------------------------------------------------------------------
// 5. Autorizzazione Livewire — metodi calendario bloccati per receptionist
// ---------------------------------------------------------------------------

it('GroupClassManager.save() lancia 403 per receptionist', function () {
    Livewire::actingAs($this->receptionist)
        ->test(GroupClassManager::class)
        ->set('formName', 'Test Corso')
        ->set('formScheduledAt', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertForbidden();
});

it('GroupClassManager.deleteClass() lancia 403 per receptionist', function () {
    $class    = GroupClass::factory()->create();
    $occurrence = ClassOccurrence::factory()->create([
        'group_class_id' => $class->id,
        'trainer_id'     => $this->trainer->id,
    ]);

    Livewire::actingAs($this->receptionist)
        ->test(GroupClassManager::class)
        ->call('deleteClass', $occurrence->id)
        ->assertForbidden();
});

it('BookingList.confirm() lancia 403 per receptionist', function () {
    $booking = PtBooking::factory()->pending()->create([
        'trainer_id' => $this->trainer->id,
        'member_id' => $this->memberOk->id,
    ]);

    Livewire::actingAs($this->receptionist)
        ->test(BookingList::class)
        ->call('confirm', $booking->id)
        ->assertForbidden();
});

it('BookingList.cancel() lancia 403 per receptionist', function () {
    $booking = PtBooking::factory()->create([
        'trainer_id' => $this->trainer->id,
        'member_id' => $this->memberOk->id,
    ]);

    Livewire::actingAs($this->receptionist)
        ->test(BookingList::class)
        ->call('openCancelModal', $booking->id)
        ->set('cancelReason', 'Motivo di test valido')
        ->call('cancel')
        ->assertForbidden();
});

it('TrainerCalendar.cancelBooking() lancia 403 per receptionist (nessuna ownership)', function () {
    $booking = PtBooking::factory()->create([
        'trainer_id' => $this->trainer->id,
        'member_id' => $this->memberOk->id,
        'status' => 'confirmed',
    ]);

    Livewire::actingAs($this->receptionist)
        ->test(TrainerCalendar::class)
        ->call('cancelBooking', $booking->id)
        ->assertForbidden();
});

it('trainer cancella il proprio booking da TrainerCalendar', function () {
    $booking = PtBooking::factory()->create([
        'trainer_id' => $this->trainer->id,
        'member_id' => $this->memberOk->id,
        'status' => 'confirmed',
    ]);

    Livewire::actingAs($this->trainer)
        ->test(TrainerCalendar::class)
        ->call('cancelBooking', $booking->id)
        ->assertHasNoErrors();

    expect($booking->fresh()->status)->toBe('cancelled');
});

it('gestore cancella il booking di un altro trainer da TrainerCalendar', function () {
    $booking = PtBooking::factory()->create([
        'trainer_id' => $this->trainer->id,
        'member_id' => $this->memberOk->id,
        'status' => 'confirmed',
    ]);

    Livewire::actingAs($this->gestore)
        ->test(TrainerCalendar::class)
        ->call('cancelBooking', $booking->id)
        ->assertHasNoErrors();

    expect($booking->fresh()->status)->toBe('cancelled');
});

// ---------------------------------------------------------------------------
// 6. Dashboard atleta — avviso certificato medico
// ---------------------------------------------------------------------------

it('athlete Dashboard mostra banner danger se cert. scaduto', function () {
    $athleteUser = User::factory()->create();
    $athleteUser->assignRole('atleta');
    Member::factory()->create([
        'user_id' => $athleteUser->id,
        'medical_cert_expiry' => now()->subDay()->toDateString(),
    ]);

    $component = Livewire::actingAs($athleteUser)
        ->test(AthleteDashboard::class);

    expect($component->get('certWarningLevel'))->toBe('danger');
    $component->assertSee('Certificato medico scaduto o mancante');
});

it('athlete Dashboard mostra banner warning se cert. scade entro 30gg', function () {
    $athleteUser = User::factory()->create();
    $athleteUser->assignRole('atleta');
    Member::factory()->create([
        'user_id' => $athleteUser->id,
        'medical_cert_expiry' => now()->addDays(15)->toDateString(),
    ]);

    $component = Livewire::actingAs($athleteUser)
        ->test(AthleteDashboard::class);

    expect($component->get('certWarningLevel'))->toBe('warning');
    $component->assertSee('Certificato medico in scadenza');
});

it('athlete Dashboard non mostra banner se cert. valido', function () {
    $athleteUser = User::factory()->create();
    $athleteUser->assignRole('atleta');
    Member::factory()->create([
        'user_id' => $athleteUser->id,
        'medical_cert_expiry' => now()->addYear()->toDateString(),
    ]);

    $component = Livewire::actingAs($athleteUser)
        ->test(AthleteDashboard::class);

    expect($component->get('certWarningLevel'))->toBe('');
    $component->assertDontSee('Certificato medico scaduto');
    $component->assertDontSee('Certificato medico in scadenza');
});

it('athlete Dashboard mostra banner danger se cert. è null', function () {
    $athleteUser = User::factory()->create();
    $athleteUser->assignRole('atleta');
    Member::factory()->create([
        'user_id' => $athleteUser->id,
        'medical_cert_expiry' => null,
    ]);

    $component = Livewire::actingAs($athleteUser)
        ->test(AthleteDashboard::class);

    expect($component->get('certWarningLevel'))->toBe('danger');
});

// ---------------------------------------------------------------------------
// 7. MemberList — visibilità link per ruolo
// ---------------------------------------------------------------------------

it('receptionist non vede link Modifica e Profilo allenamento in MemberList', function () {
    $athleteUser = User::factory()->create();
    $athleteUser->assignRole('atleta');
    Member::factory()->create([
        'user_id' => $athleteUser->id,
        'medical_cert_expiry' => now()->addYear()->toDateString(),
    ]);

    Livewire::actingAs($this->receptionist)
        ->test(MemberList::class)
        ->assertDontSee(route('backoffice.members.edit', Member::first()))
        ->assertDontSee('Profilo allenamento');
});

it('gestore vede link Modifica e Profilo allenamento in MemberList', function () {
    $athleteUser = User::factory()->create();
    $athleteUser->assignRole('atleta');
    $member = Member::factory()->create([
        'user_id' => $athleteUser->id,
        'medical_cert_expiry' => now()->addYear()->toDateString(),
    ]);

    Livewire::actingAs($this->gestore)
        ->test(MemberList::class)
        ->assertSee(route('backoffice.members.edit', $member))
        ->assertSee('Profilo allenamento');
});

// ---------------------------------------------------------------------------
// 8. GroupClassManager.removeParticipant() — consentito al receptionist
// ---------------------------------------------------------------------------

it('receptionist può rimuovere partecipante da corso (operazione front-desk)', function () {
    $class      = GroupClass::factory()->create();
    $occurrence = ClassOccurrence::factory()->create([
        'group_class_id' => $class->id,
        'trainer_id'     => $this->trainer->id,
        'capacity'       => 10,
    ]);
    $booking = ClassBooking::factory()->create([
        'class_occurrence_id' => $occurrence->id,
        'member_id'           => $this->memberOk->id,
        'status'              => 'confirmed',
    ]);

    Livewire::actingAs($this->receptionist)
        ->test(GroupClassManager::class)
        ->call('removeParticipant', $booking->id)
        ->assertHasNoErrors();

    expect($booking->fresh()->status)->toBe('cancelled_by_athlete');
});

// ---------------------------------------------------------------------------
// 9. Sezioni TRAINING e ADMIN — receptionist riceve 403
// ---------------------------------------------------------------------------

it('receptionist ottiene 403 su ExerciseList', function () {
    Livewire::actingAs($this->receptionist)
        ->test(ExerciseList::class)
        ->assertForbidden();
});

it('receptionist ottiene 403 su TemplateList', function () {
    Livewire::actingAs($this->receptionist)
        ->test(TemplateList::class)
        ->assertForbidden();
});

it('receptionist ottiene 403 su MesocycleList', function () {
    Livewire::actingAs($this->receptionist)
        ->test(MesocycleList::class)
        ->assertForbidden();
});

it('receptionist ottiene 403 su PlateInventoryManager', function () {
    Livewire::actingAs($this->receptionist)
        ->test(PlateInventoryManager::class)
        ->assertForbidden();
});

it('gestore accede a ExerciseList senza 403', function () {
    Livewire::actingAs($this->gestore)
        ->test(ExerciseList::class)
        ->assertOk();
});

it('trainer accede a TemplateList senza 403', function () {
    Livewire::actingAs($this->trainer)
        ->test(TemplateList::class)
        ->assertOk();
});

it('trainer non accede a PlateInventoryManager', function () {
    Livewire::actingAs($this->trainer)
        ->test(PlateInventoryManager::class)
        ->assertForbidden();
});
