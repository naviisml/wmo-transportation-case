<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/dashboard')->name('home');
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');
    Route::get('municipality', fn () => Inertia::render('Municipality/Overview'))
        ->middleware('can:access-municipality')->name('municipality.overview');

    Route::middleware('can:access-residents')->group(function () {
        Route::get('residents', fn () => Inertia::render('Residents/Index'))->name('residents.index');
        Route::get('residents/{resident}', fn (int $resident) => Inertia::render('Residents/Show', ['residentId' => $resident]))
            ->whereNumber('resident')->name('residents.show');
    });
});
require __DIR__.'/settings.php';
