<?php

use App\Jobs\ExportFinancialReportCsv;
use App\Jobs\ExportMembersListCsv;
use App\Models\Member;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

it("l'export finanziario CSV contiene le righe corrette", function () {
    $user = User::factory()->create();

    $plan = SubscriptionPlan::create([
        'name' => 'Mensile',
        'price_cents' => 5000,
        'duration_days' => 30,
        'is_active' => true,
    ]);

    $member = Member::factory()->create(['fiscal_code' => 'TSTMRC80A01H501Z']);

    Subscription::create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'started_at' => now()->year.'-03-01',
        'expires_at' => now()->year.'-03-31',
        'status' => 'active',
    ]);

    (new ExportFinancialReportCsv(now()->year, $user->id))->handle();

    $files = Storage::disk('local')->files('private/reports');
    expect($files)->not->toBeEmpty();

    $content = Storage::disk('local')->get($files[0]);

    expect($content)
        ->toContain('Mensile')
        ->toContain('50,00')
        ->toContain('TSTMRC80A01H501Z');
});

it("l'export anagrafica CSV contiene le colonne corrette", function () {
    $user = User::factory()->create();

    $plan = SubscriptionPlan::create([
        'name' => 'Mensile',
        'price_cents' => 5000,
        'duration_days' => 30,
        'is_active' => true,
    ]);

    $member = Member::factory()->create([
        'first_name' => 'Giovanni',
        'last_name' => 'Verdi',
        'email' => 'giovanni.verdi@example.com',
        'fiscal_code' => 'VRDGNN70B02F205H',
    ]);

    Subscription::create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'started_at' => now()->toDateString(),
        'expires_at' => now()->addDays(30)->toDateString(),
        'status' => 'active',
    ]);

    (new ExportMembersListCsv($user->id))->handle();

    $files = Storage::disk('local')->files('private/reports');
    expect($files)->not->toBeEmpty();

    $content = Storage::disk('local')->get($files[0]);

    expect($content)
        ->toContain('Cognome')
        ->toContain('Verdi')
        ->toContain('Giovanni')
        ->toContain('giovanni.verdi@example.com')
        ->toContain('VRDGNN70B02F205H')
        ->toContain('Attivo');
});
