<?php

use App\Livewire\Backoffice\Access\AccessLogList;
use App\Livewire\Backoffice\Dashboard;
use App\Livewire\Backoffice\Members\MemberForm;
use App\Livewire\Backoffice\Members\MemberList;
use App\Livewire\Backoffice\Subscriptions\SubscriptionForm;
use App\Livewire\Backoffice\Subscriptions\SubscriptionList;
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
    });
