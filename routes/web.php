<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])
    ->middleware('throttle:5,1')->name('admin.login');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function (): void {
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::post('/licenses', [AdminDashboardController::class, 'storeLicense'])->name('licenses.store');
    Route::post('/licenses/{license}/revoke', [AdminDashboardController::class, 'revokeLicense'])->name('licenses.revoke');
    Route::post('/licenses/{license}/extend', [AdminDashboardController::class, 'extendLicense'])->name('licenses.extend');
    Route::post('/activations/{activation}/deactivate', [AdminDashboardController::class, 'deactivateDevice'])->name('activations.deactivate');
    Route::post('/users/{user}/toggle-status', [AdminDashboardController::class, 'toggleUserStatus'])->name('users.toggle-status');
    Route::post('/packages/{package}/update', [AdminDashboardController::class, 'updatePackageLimits'])->name('packages.update');
    Route::post('/packages/{package}/features', [AdminDashboardController::class, 'updatePackageFeatures'])->name('packages.features');
});
