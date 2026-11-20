<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/dashboard')->name('home');
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');
    Route::get('municipality', fn () => Inertia::render('Municipality/Overview'))
        ->middleware('can:access-municipality')->name('municipality.overview');
});
require __DIR__.'/settings.php';
