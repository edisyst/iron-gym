<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

// Reindirizza la dashboard Breeze al backoffice per gli utenti autenticati
Route::get('dashboard', function () {
    return redirect()->route('backoffice.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
require __DIR__.'/backoffice.php';
require __DIR__.'/athlete.php';
