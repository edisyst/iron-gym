<?php

namespace App\Observers;

use App\Models\Subscription;
use Illuminate\Support\Facades\Cache;

class SubscriptionObserver
{
    public function saved(Subscription $subscription): void
    {
        Cache::tags(['kpi'])->flush();
    }

    public function deleted(Subscription $subscription): void
    {
        Cache::tags(['kpi'])->flush();
    }
}
