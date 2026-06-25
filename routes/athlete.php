<?php

use App\Http\Controllers\ProgressPhotoController;
use App\Http\Controllers\PushSubscriptionController;
use App\Livewire\Athlete\BodyMeasurementForm;
use App\Livewire\Athlete\Booking;
use App\Livewire\Athlete\Dashboard;
use App\Livewire\Athlete\ExerciseCatalog;
use App\Livewire\Athlete\ExerciseDetail as AthleteExerciseDetail;
use App\Livewire\Athlete\Messages;
use App\Livewire\Athlete\Profile as AthleteProfile;
use App\Livewire\Athlete\ProgressPhotoUpload;
use App\Livewire\Athlete\TrainingHub;
use App\Livewire\Athlete\WorkoutSession;
use App\Models\Message;
use Illuminate\Support\Facades\Route;

Route::prefix('athlete')
    ->middleware(['auth', 'role:atleta'])
    ->name('athlete.')
    ->group(function () {
        Route::get('/', Dashboard::class)->name('dashboard');
        Route::get('/session/{session}', WorkoutSession::class)->name('session');
        Route::get('/history', TrainingHub::class)->name('history');
        Route::get('/progress', fn () => redirect()->route('athlete.history'))->name('progress');
        Route::get('/measurements', BodyMeasurementForm::class)->name('measurements');
        Route::get('/photos/upload', ProgressPhotoUpload::class)->name('photos.upload');
        Route::get('/photos/{progressPhoto}', [ProgressPhotoController::class, 'show'])->name('photos.show');

        // Catalogo esercizi atleta
        Route::get('/exercises', ExerciseCatalog::class)->name('exercises.index');
        Route::get('/exercises/{exercise:slug}', AthleteExerciseDetail::class)->name('exercises.show');

        // Step 6 — prenotazioni
        Route::get('/bookings', Booking::class)->name('bookings');

        Route::get('/profile', AthleteProfile::class)->name('profile');

        // Step 7 — messaggistica e push
        Route::get('/messages', Messages::class)->name('messages');
        Route::get('/messages-unread-count', function () {
            return response()->json(['count' => Message::where('receiver_id', auth()->id())->whereNull('read_at')->count()]);
        })->name('messages.unread-count');
        Route::post('/push-subscribe', [PushSubscriptionController::class, 'store'])->name('push-subscribe');
    });
