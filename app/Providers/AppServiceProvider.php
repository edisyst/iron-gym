<?php

namespace App\Providers;

use App\Models\Exercise;
use App\Models\PtBooking;
use App\Models\Subscription;
use App\Models\TrainerAvailability;
use App\Models\TrainingSession;
use App\Observers\ExerciseObserver;
use App\Observers\PtBookingObserver;
use App\Observers\SubscriptionObserver;
use App\Observers\TrainerAvailabilityObserver;
use App\Observers\TrainingSessionObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Exercise::observe(ExerciseObserver::class);
        Subscription::observe(SubscriptionObserver::class);
        TrainingSession::observe(TrainingSessionObserver::class);
        TrainerAvailability::observe(TrainerAvailabilityObserver::class);
        PtBooking::observe(PtBookingObserver::class);
    }
}
