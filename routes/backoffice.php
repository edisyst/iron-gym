<?php

use App\Livewire\Backoffice\Access\AccessLogList;
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

        // Libreria esercizi (Step 2)
        Route::get('/exercises', ExerciseList::class)->name('exercises.index');
        Route::get('/exercises/create', ExerciseForm::class)->name('exercises.create');
        Route::get('/exercises/{exercise}', ExerciseDetail::class)->name('exercises.show');
        Route::get('/exercises/{exercise}/edit', ExerciseForm::class)->name('exercises.edit');

        // Template schede (Step 2)
        Route::get('/templates', TemplateList::class)->name('templates.index');
        Route::get('/templates/create', TemplateForm::class)->name('templates.create');
        Route::get('/templates/{template}/builder', TemplateBuilder::class)->name('templates.builder');

        // Mesocicli (Step 3 + Step 4)
        Route::get('/mesocycles', MesocycleList::class)->name('mesocycles.index');
        Route::get('/mesocycles/assign', MesocycleAssign::class)->name('mesocycles.assign');
        Route::get('/mesocycles/{mesocycle}', MesocycleDetail::class)->name('mesocycles.show');
        Route::get('/athletes/{athleteId}/volume-landmarks', VolumeLandmarkManager::class)->name('athletes.volume-landmarks');
    });
