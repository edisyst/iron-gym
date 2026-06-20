<?php

use App\Jobs\HealthCheckJob;
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

// Health check heartbeat — dispatchato ogni minuto, letto da HealthCheckController
Schedule::job(HealthCheckJob::class)->everyMinute();

// Backup
Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('02:00');
