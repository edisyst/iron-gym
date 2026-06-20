<?php

use App\Jobs\SendMedicalCertExpiryReminders;
use App\Jobs\SendSubscriptionExpiryReminders;
use App\Models\Member;
use App\Models\Message;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\MedicalCertExpiryNotification;
use App\Notifications\SubscriptionExpiryNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('un membro con certificato in scadenza riceve la notifica', function () {
    Notification::fake();

    $user = User::factory()->create();
    $member = Member::factory()->create([
        'user_id' => $user->id,
        'medical_cert_expiry' => Carbon::today()->addDays(15),
        'is_active' => true,
    ]);

    (new SendMedicalCertExpiryReminders)->handle();

    Notification::assertSentTo($user, MedicalCertExpiryNotification::class);
});

it('un membro con abbonamento scaduto non riceve la notifica di scadenza imminente', function () {
    Notification::fake();

    $user = User::factory()->create();
    $member = Member::factory()->create(['user_id' => $user->id, 'is_active' => true]);

    $plan = SubscriptionPlan::create([
        'name' => 'Test Plan', 'price_cents' => 5000, 'duration_days' => 30,
        'is_active' => true,
    ]);

    Subscription::create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'started_at' => Carbon::today()->subDays(60),
        'expires_at' => Carbon::today()->subDays(30),
        'status' => 'expired',
    ]);

    (new SendSubscriptionExpiryReminders)->handle();

    Notification::assertNotSentTo($user, SubscriptionExpiryNotification::class);
});

it('un messaggio inviato incrementa il contatore non letti del destinatario', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    Message::create([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'body' => 'Ciao atleta!',
    ]);

    $unread = Message::where('receiver_id', $receiver->id)->whereNull('read_at')->count();

    expect($unread)->toBe(1);
});
