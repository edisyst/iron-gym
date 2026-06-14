<?php

use App\Livewire\Athlete\Dashboard;
use App\Livewire\Athlete\History;
use App\Livewire\Athlete\WorkoutSession;
use Illuminate\Support\Facades\Route;

Route::prefix('athlete')
    ->middleware(['auth', 'role:atleta'])
    ->name('athlete.')
    ->group(function () {
        Route::get('/', Dashboard::class)->name('dashboard');
        Route::get('/session/{session}', WorkoutSession::class)->name('session');
        Route::get('/history', History::class)->name('history');
    });
