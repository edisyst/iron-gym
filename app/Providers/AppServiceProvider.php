<?php

namespace App\Providers;

use App\Channels\WebPushChannel;
use App\Models\Exercise;
use App\Models\PtBooking;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\TrainerAvailability;
use App\Models\TrainingSession;
use App\Models\User;
use App\Observers\ExerciseObserver;
use App\Observers\PtBookingObserver;
use App\Observers\SubscriptionObserver;
use App\Observers\TrainerAvailabilityObserver;
use App\Observers\TrainingSessionObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
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
        Paginator::useBootstrap();

        Exercise::observe(ExerciseObserver::class);
        Subscription::observe(SubscriptionObserver::class);
        TrainingSession::observe(TrainingSessionObserver::class);
        TrainerAvailability::observe(TrainerAvailabilityObserver::class);
        PtBooking::observe(PtBookingObserver::class);

        $this->defineFeatureFlags();
        $this->defineGates();
        $this->configureFlare();
        $this->registerBladeDirectives();
        $this->registerNotificationChannels();
    }

    private function defineFeatureFlags(): void
    {
        // Tutti i flag gestibili da UI usano il pattern:
        //   Setting::bool(chiave, default) && <condizione_scope>
        // Spegnere l'interruttore spegne per tutti; accenderlo non allarga
        // la platea rispetto alla condizione scope preesistente.
        // Il toggle scrive su settings e chiama Feature::purge() cosi' anche
        // gli utenti che non avevano mai risolto il flag lo rileggono.

        // --- Moduli ---

        Feature::define('periodization_engine', fn (User $user) => Setting::bool('periodization_engine_enabled', true) &&
            ($user->hasRole('gestore') || in_array($user->email, config('features.beta_trainers', [])))
        );

        Feature::define('push_notifications', fn (User $user) => Setting::bool('push_notifications_enabled', false) &&
            $user->hasRole(['atleta', 'trainer'])
        );

        Feature::define('group_classes', fn (): bool => Setting::bool(
            'group_classes_enabled',
            (bool) config('features.group_classes_enabled', false)
        ));

        Feature::define('financial_reports', fn (User $user) => Setting::bool('financial_reports_enabled', true) &&
            $user->hasRole('gestore')
        );

        Feature::define('messaging', fn (): bool => Setting::bool('messaging_enabled', true));

        Feature::define('pt_bookings', fn (): bool => Setting::bool('pt_bookings_enabled', true));

        Feature::define('outbound_notifications', fn (): bool => Setting::bool('outbound_notifications_enabled', true));

        Feature::define('in_app_feedback', fn (): bool => Setting::bool(
            'in_app_feedback_enabled',
            (bool) config('features.in_app_feedback_enabled', false)
        ));

        // --- Sessione atleta ---

        Feature::define('readiness_check', fn (): bool => Setting::bool('readiness_check_enabled', true));

        Feature::define('exercise_substitution', fn (): bool => Setting::bool('exercise_substitution_enabled', true));

        Feature::define('session_recap', fn (): bool => Setting::bool('session_recap_enabled', true));

        Feature::define('personal_records', fn (): bool => Setting::bool('personal_records_enabled', true));

        Feature::define('weekly_volume', fn (): bool => Setting::bool('weekly_volume_enabled', true));
    }

    private function defineGates(): void
    {
        // Gate usato da AdminLTE sidebar per la voce "Corsi collettivi"
        Gate::define('view-group-classes', fn () => Feature::active('group_classes'));

        // Gate usato da AdminLTE sidebar per la voce "Report allenamento"
        Gate::define('view-training-reports', fn (User $user) => ! $user->hasRole('receptionist'));

        // Gate usati da AdminLTE sidebar per voci riservate a trainer/gestore
        Gate::define('manage-trainer-availability', fn (User $user) => $user->hasAnyRole(['gestore', 'trainer']));
        Gate::define('send-campaigns', fn (User $user) => $user->hasRole('gestore'));

        // Gate per sezioni TRAINING e ADMIN: esclusi receptionist e atleta
        Gate::define('access-training-section', fn (User $user) => $user->hasAnyRole(['gestore', 'trainer']));
        Gate::define('access-admin-section', fn (User $user) => $user->hasRole('gestore'));

        // Gate per sezioni a lettura-only per trainer
        Gate::define('view-access-logs', fn (User $user) => $user->hasAnyRole(['gestore', 'receptionist']));
        Gate::define('manage-members', fn (User $user) => $user->hasAnyRole(['gestore', 'receptionist']));
        Gate::define('manage-subscriptions', fn (User $user) => $user->hasAnyRole(['gestore', 'receptionist']));

        // Gate feature-gated reports (completano il role:gestore gia' presente sulla route)
        Gate::define('view-financial-reports', fn (User $user) => Feature::for($user)->active('financial_reports'));

        // Gate per moduli flaggabili (route middleware + sidebar)
        Gate::define('view-messaging', fn () => Feature::active('messaging'));
        Gate::define('enroll-pt-bookings', fn () => Feature::active('pt_bookings'));
        Gate::define('view-session-recap', fn () => Feature::active('session_recap'));
        Gate::define('view-personal-records', fn () => Feature::active('personal_records'));
        Gate::define('view-weekly-volume', fn () => Feature::active('weekly_volume'));
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

    private function registerNotificationChannels(): void
    {
        Notification::extend('webpush', fn () => app(WebPushChannel::class));
    }
}
