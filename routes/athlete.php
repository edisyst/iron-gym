<?php

use App\Http\Controllers\ProgressPhotoController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\SyncBatchController;
use App\Livewire\Athlete\BodyMeasurementForm;
use App\Livewire\Athlete\Booking;
use App\Livewire\Athlete\Dashboard;
use App\Livewire\Athlete\ExerciseCatalog;
use App\Livewire\Athlete\ExerciseDetail as AthleteExerciseDetail;
use App\Livewire\Athlete\Messages;
use App\Livewire\Athlete\Notifications as AthleteNotifications;
use App\Livewire\Athlete\PersonalRecords;
use App\Livewire\Athlete\Profile as AthleteProfile;
use App\Livewire\Athlete\ProgressPhotoUpload;
use App\Livewire\Athlete\SessionRecap;
use App\Livewire\Athlete\TrainingHub;
use App\Livewire\Athlete\WeeklyVolume;
use App\Livewire\Athlete\WorkoutSession;
use App\Models\Message;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::prefix('athlete')
    ->middleware(['auth', 'role:atleta'])
    ->name('athlete.')
    ->group(function () {
        Route::get('/', Dashboard::class)->name('dashboard');
        // Alias: /athlete/dashboard e' l'URL che ci si aspetta per convenzione
        Route::get('/dashboard', fn () => redirect()->route('athlete.dashboard'));
        Route::get('/session/{session}', WorkoutSession::class)->name('session');
        Route::get('/session/{session}/recap', SessionRecap::class)
            ->middleware('can:view-session-recap')
            ->name('session.recap');
        Route::get('/history', TrainingHub::class)->name('history');
        Route::get('/progress', fn () => redirect()->route('athlete.history'))->name('progress');
        Route::get('/measurements', BodyMeasurementForm::class)->name('measurements');
        Route::get('/photos/upload', ProgressPhotoUpload::class)->name('photos.upload');
        Route::get('/photos/{progressPhoto}', [ProgressPhotoController::class, 'show'])->name('photos.show');

        // Catalogo esercizi atleta
        Route::get('/exercises', ExerciseCatalog::class)->name('exercises.index');
        Route::get('/exercises/{exercise:slug}', AthleteExerciseDetail::class)->name('exercises.show');

        // Step 6 — prenotazioni
        Route::get('/bookings', Booking::class)
            ->middleware('can:view-athlete-bookings')
            ->name('bookings');

        Route::get('/volume', WeeklyVolume::class)
            ->middleware('can:view-weekly-volume')
            ->name('volume');
        Route::get('/records', PersonalRecords::class)
            ->middleware('can:view-personal-records')
            ->name('records');
        Route::get('/profile', AthleteProfile::class)->name('profile');

        // Step 7 — messaggistica e push (gated: feature messaging)
        Route::get('/messages', Messages::class)
            ->middleware('can:view-messaging')
            ->name('messages');
        Route::get('/messages-unread-count', function () {
            if (! Gate::allows('view-messaging')) {
                return response()->json(['count' => 0]);
            }

            return response()->json(['count' => Message::where('receiver_id', auth()->id())->whereNull('read_at')->count()]);
        })->name('messages.unread-count');
        Route::post('/push-subscribe', [PushSubscriptionController::class, 'store'])->name('push-subscribe');

        // R10 — notification center
        Route::get('/notifications', AthleteNotifications::class)->name('notifications');
        Route::get('/notifications-unread-count', function () {
            return response()->json(['count' => auth()->user()->unreadNotifications()->count()]);
        })->name('notifications.unread-count');

        // Release 03 — sync offline queue
        Route::post('/session/sync', [SyncBatchController::class, 'handle'])->name('session.sync');
    });
