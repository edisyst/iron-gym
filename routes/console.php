<?php

use App\Jobs\SendMedicalCertExpiryReminders;
use App\Jobs\SendSessionReminders;
use App\Jobs\SendSubscriptionExpiryReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(SendMedicalCertExpiryReminders::class)->dailyAt('09:00');
Schedule::job(SendSubscriptionExpiryReminders::class)->dailyAt('09:00');
Schedule::job(SendSessionReminders::class)->everyFifteenMinutes();
