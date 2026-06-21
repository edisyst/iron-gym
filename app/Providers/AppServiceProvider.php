<?php

namespace App\Providers;

use App\Models\Exercise;
use App\Models\PtBooking;
use App\Models\Subscription;
use App\Models\TrainerAvailability;
use App\Models\TrainingSession;
use App\Models\User;
use App\Observers\ExerciseObserver;
use App\Observers\PtBookingObserver;
use App\Observers\SubscriptionObserver;
use App\Observers\TrainerAvailabilityObserver;
use App\Observers\TrainingSessionObserver;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;
use Spatie\LaravelFlare\Facades\Flare;

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

        $this->defineFeatureFlags();
        $this->defineGates();
        $this->configureFlare();
        $this->registerBladeDirectives();
    }

    private function defineFeatureFlags(): void
    {
        Feature::define('periodization_engine', fn (User $user) => $user->hasRole('gestore') || in_array($user->email, config('features.beta_trainers', []))
        );

        Feature::define('push_notifications', fn (User $user) => $user->hasRole(['atleta', 'trainer'])
        );

        Feature::define('group_classes', function (): bool {
            return (bool) config('features.group_classes_enabled', false);
        });

        Feature::define('financial_reports', fn (User $user) => $user->hasRole('gestore')
        );
    }

    private function defineGates(): void
    {
        // Gate usato da AdminLTE sidebar per la voce "Corsi collettivi"
        Gate::define('view-group-classes', fn () => Feature::active('group_classes'));
    }

    private function configureFlare(): void
    {
        if (! app()->bound(Flare::class) || ! config('flare.key')) {
            return;
        }

        Flare::context('User', fn () => [
            'id' => auth()->id(),
            'email' => auth()->user()?->email,
            'roles' => auth()->user()?->getRoleNames()->join(', '),
        ]);
    }

    private function registerBladeDirectives(): void
    {
        Blade::if('feature', fn (string $flag) => Feature::active($flag));
    }
}
