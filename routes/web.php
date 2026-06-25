<?php

use App\Http\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthCheckController::class);

Route::view('/', 'welcome');

// Reindirizza al portale corretto in base al ruolo
Route::get('dashboard', function () {
    if (auth()->user()->hasRole('atleta')) {
        return redirect()->route('athlete.dashboard');
    }

    return redirect()->route('backoffice.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('profile', function () {
    if (auth()->user()->hasRole('atleta')) {
        return redirect()->route('athlete.profile');
    }

    return view('profile');
})->middleware(['auth'])->name('profile');

require __DIR__.'/auth.php';
require __DIR__.'/backoffice.php';
require __DIR__.'/athlete.php';
