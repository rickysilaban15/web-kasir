<?php

use App\Http\Controllers\Api\AppConfigController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LicenseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // Public Authentication Endpoints
    Route::post('/auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1');
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    // Authenticated Account Endpoints
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // License Activation & Validation Endpoints
    Route::prefix('licenses')->middleware('throttle:20,1')->group(function (): void {
        Route::post('/activate', [LicenseController::class, 'activate']);
        Route::get('/status', [LicenseController::class, 'status']);
        Route::post('/validate', [LicenseController::class, 'validateLicense']);
        Route::post('/deactivate-device', [LicenseController::class, 'deactivateDevice']);
    });

    // Application Config & Version Endpoints
    Route::get('/app/config', [AppConfigController::class, 'config']);
    Route::get('/app/version', [AppConfigController::class, 'version']);
});

