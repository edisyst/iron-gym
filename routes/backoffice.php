<?php

use App\Livewire\Backoffice\Access\AccessLogList;
use App\Livewire\Backoffice\Admin\FeatureFlagManager;
use App\Livewire\Backoffice\Admin\FeedbackList;
use App\Livewire\Backoffice\Athletes\AthleteAnalytics;
use App\Livewire\Backoffice\Athletes\AthleteProfile;
use App\Livewire\Backoffice\Athletes\BodyMeasurementForm;
use App\Livewire\Backoffice\Calendar\AvailabilityManager;
use App\Livewire\Backoffice\Calendar\BookingList;
use App\Livewire\Backoffice\Calendar\GroupClassManager;
use App\Livewire\Backoffice\Calendar\TrainerCalendar;
use App\Livewire\Backoffice\Communications\CommunicationCampaign;
use App\Livewire\Backoffice\Dashboard;
use App\Livewire\Backoffice\Exercises\ExerciseDetail;
use App\Livewire\Backoffice\Exercises\ExerciseForm;
use App\Livewire\Backoffice\Exercises\ExerciseList;
use App\Livewire\Backoffice\Members\MemberForm;
use App\Livewire\Backoffice\Members\MemberList;
use App\Livewire\Backoffice\Mesocycles\MesocycleAssign;
use App\Livewire\Backoffice\Mesocycles\MesocycleDetail;
use App\Livewire\Backoffice\Mesocycles\MesocycleList;
use App\Livewire\Backoffice\Mesocycles\VolumeLandmarkManager;
use App\Livewire\Backoffice\Messages\MessageThread;
use App\Livewire\Backoffice\Reports\FinancialReport;
use App\Livewire\Backoffice\Reports\ManagerDashboard;
use App\Livewire\Backoffice\Reports\TrainingReport;
use App\Livewire\Backoffice\Subscriptions\SubscriptionForm;
use App\Livewire\Backoffice\Subscriptions\SubscriptionList;
use App\Livewire\Backoffice\Templates\TemplateBuilder;
use App\Livewire\Backoffice\Templates\TemplateForm;
use App\Livewire\Backoffice\Templates\TemplateList;
use Illuminate\Support\Facades\Route;

Route::prefix('backoffice')
    ->middleware(['auth', 'role:gestore|trainer|receptionist'])
    ->name('backoffice.')
    ->group(function () {
        Route::get('/dashboard', Dashboard::class)->name('dashboard');

        Route::get('/members', MemberList::class)->name('members.index');
        Route::get('/members/create', MemberForm::class)->name('members.create');
        Route::get('/members/{member}/edit', MemberForm::class)->name('members.edit');

        Route::get('/subscriptions', SubscriptionList::class)->name('subscriptions.index');
        Route::get('/subscriptions/create', SubscriptionForm::class)->name('subscriptions.create');

        Route::get('/access-logs', AccessLogList::class)->name('access-logs.index');

        // Libreria esercizi — lista e dettaglio: visibili anche al receptionist
        Route::get('/exercises', ExerciseList::class)->name('exercises.index');
        Route::get('/exercises/{exercise:slug}', ExerciseDetail::class)->name('exercises.show');

        // Template schede — lista: visibile anche al receptionist
        Route::get('/templates', TemplateList::class)->name('templates.index');

        // Mesocicli — lista: visibile anche al receptionist
        Route::get('/mesocycles', MesocycleList::class)->name('mesocycles.index');

        // Step 6 — prenotazioni e calendario (receptionist gestisce queste)
        Route::get('/calendar', TrainerCalendar::class)->name('calendar.index');
        Route::get('/calendar/availability', AvailabilityManager::class)->name('calendar.availability');
        Route::get('/bookings', BookingList::class)->name('bookings.index');
        Route::get('/group-classes', GroupClassManager::class)->name('group-classes.index');

        // Route riservate a trainer e gestore (mutano dati training o espongono dati medici)
        Route::middleware('role:gestore|trainer')->group(function () {
            // Libreria esercizi — creazione e modifica
            Route::get('/exercises/create', ExerciseForm::class)->name('exercises.create');
            Route::get('/exercises/{exercise:slug}/edit', ExerciseForm::class)->name('exercises.edit');

            // Template schede — creazione e builder
            Route::get('/templates/create', TemplateForm::class)->name('templates.create');
            Route::get('/templates/{template}/builder', TemplateBuilder::class)->name('templates.builder');

            // Mesocicli — assegnazione e dettaglio (con applyProgression/forceDeload)
            Route::get('/mesocycles/assign', MesocycleAssign::class)->name('mesocycles.assign');
            Route::get('/mesocycles/{mesocycleId}', MesocycleDetail::class)->name('mesocycles.show');
            Route::get('/athletes/{athleteId}/volume-landmarks', VolumeLandmarkManager::class)->name('athletes.volume-landmarks');

            // Step 5 — tracking corporeo e analytics (dati medico-sportivi)
            Route::get('/athletes/{athleteId}/measurements', BodyMeasurementForm::class)->name('athletes.measurements');
            Route::get('/athletes/{athleteId}/analytics', AthleteAnalytics::class)->name('athletes.analytics');
            Route::get('/athletes/{athleteId}/profile', AthleteProfile::class)->name('athletes.profile');

            // Step 7 — messaggistica e comunicazione con atleti
            Route::get('/athletes/{athleteId}/messages', MessageThread::class)->name('athletes.messages');
            Route::get('/communications/campaign', CommunicationCampaign::class)->name('communications.campaign');
        });

        // Step 8 — reportistica gestore
        Route::get('/reports/manager', ManagerDashboard::class)
            ->middleware('role:gestore')
            ->name('reports.manager');

        Route::get('/reports/financial', FinancialReport::class)
            ->middleware('role:gestore')
            ->name('reports.financial');

        Route::get('/reports/training', TrainingReport::class)
            ->middleware('role:gestore|trainer')
            ->name('reports.training');

        // Step 10 — admin tools (solo gestore)
        Route::middleware('role:gestore')->group(function () {
            Route::get('/admin/feature-flags', FeatureFlagManager::class)->name('admin.feature-flags');
            Route::get('/admin/feedback', FeedbackList::class)->name('admin.feedback');
        });

        Route::get('/reports/download/{file}', function (string $file) {
            // Sicurezza: solo nome file senza path traversal
            $basename = basename($file);
            $path = storage_path("app/private/reports/{$basename}");

            abort_unless(file_exists($path), 404);

            return response()->download($path, $basename);
        })->middleware('role:gestore')->name('reports.download');
    });
