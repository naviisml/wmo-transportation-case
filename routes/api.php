<?php

use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\ResidentController;
use App\Http\Middleware\AuditApiAccess;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:wmo-api', AuditApiAccess::class])->group(function () {
    Route::get('me', MeController::class);
    Route::get('residents', [ResidentController::class, 'index']);
    Route::get('residents/{resident}', [ResidentController::class, 'show'])->whereNumber('resident');
});
