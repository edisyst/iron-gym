<?php

use App\Jobs\NotifyClassCancellation;
use App\Jobs\NotifyWaitlistPromotion;
use App\Jobs\SendCampaignMessages;
use App\Jobs\SendClassReminders;
use App\Jobs\SendMedicalCertExpiryReminders;
use App\Jobs\SendSessionReminders;
use App\Jobs\SendSubscriptionExpiryReminders;
use App\Models\ClassBooking;
use App\Models\ClassOccurrence;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    Setting::write('outbound_notifications_enabled', false);
});

it('SendSubscriptionExpiryReminders rispetta kill switch', function () {
    (new SendSubscriptionExpiryReminders)->handle();

    Notification::assertNothingSent();
});

it('SendMedicalCertExpiryReminders rispetta kill switch', function () {
    (new SendMedicalCertExpiryReminders)->handle();

    Notification::assertNothingSent();
});

it('SendSessionReminders rispetta kill switch', function () {
    (new SendSessionReminders)->handle();

    Notification::assertNothingSent();
});

it('SendClassReminders rispetta kill switch', function () {
    (new SendClassReminders)->handle();

    Notification::assertNothingSent();
});

it('NotifyClassCancellation rispetta kill switch', function () {
    $occurrence = ClassOccurrence::factory()->create();

    (new NotifyClassCancellation($occurrence))->handle();

    Notification::assertNothingSent();
});

it('NotifyWaitlistPromotion rispetta kill switch', function () {
    $booking = ClassBooking::factory()->create();

    (new NotifyWaitlistPromotion($booking))->handle();

    Notification::assertNothingSent();
});

it('SendCampaignMessages rispetta kill switch', function () {
    $job = new SendCampaignMessages([], 'email', null, 'Test');

    $job->handle();

    Notification::assertNothingSent();
});
