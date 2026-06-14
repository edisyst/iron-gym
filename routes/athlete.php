<?php

use App\Http\Controllers\ProgressPhotoController;
use App\Livewire\Athlete\BodyMeasurementForm;
use App\Livewire\Athlete\Dashboard;
use App\Livewire\Athlete\History;
use App\Livewire\Athlete\Progress;
use App\Livewire\Athlete\ProgressPhotoUpload;
use App\Livewire\Athlete\WorkoutSession;
use Illuminate\Support\Facades\Route;

Route::prefix('athlete')
    ->middleware(['auth', 'role:atleta'])
    ->name('athlete.')
    ->group(function () {
        Route::get('/', Dashboard::class)->name('dashboard');
        Route::get('/session/{session}', WorkoutSession::class)->name('session');
        Route::get('/history', History::class)->name('history');
        Route::get('/progress', Progress::class)->name('progress');
        Route::get('/measurements', BodyMeasurementForm::class)->name('measurements');
        Route::get('/photos/upload', ProgressPhotoUpload::class)->name('photos.upload');
        Route::get('/photos/{progressPhoto}', [ProgressPhotoController::class, 'show'])->name('photos.show');
    });
