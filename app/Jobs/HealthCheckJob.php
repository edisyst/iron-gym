<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class HealthCheckJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Cache::put('health_check_heartbeat', now()->toIso8601String(), 70);
    }
}
