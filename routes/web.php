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
        Route::get('book-ride', fn () => Inertia::render('Rides/SelectResident'))->name('rides.select-resident');
        Route::get('residents/{resident}/rides/create', fn (int $resident) => Inertia::render('Rides/Create', ['residentId' => $resident]))
            ->whereNumber('resident')->name('rides.create');
    });
    Route::get('rides', fn () => Inertia::render('Rides/Index'))
        ->middleware('can:access-rides')->name('rides.index');
});
require __DIR__.'/settings.php';
