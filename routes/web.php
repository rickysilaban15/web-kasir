<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::post('/licenses', [AdminDashboardController::class, 'storeLicense'])->name('licenses.store');
    Route::post('/licenses/{license}/revoke', [AdminDashboardController::class, 'revokeLicense'])->name('licenses.revoke');
    Route::post('/licenses/{license}/extend', [AdminDashboardController::class, 'extendLicense'])->name('licenses.extend');
    Route::post('/activations/{activation}/deactivate', [AdminDashboardController::class, 'deactivateDevice'])->name('activations.deactivate');
    Route::post('/users/{user}/toggle-status', [AdminDashboardController::class, 'toggleUserStatus'])->name('users.toggle-status');
    Route::post('/packages/{package}/update', [AdminDashboardController::class, 'updatePackageLimits'])->name('packages.update');
});
