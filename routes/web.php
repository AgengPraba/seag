<?php

use App\Http\Controllers\Api\AccessVerificationController;
use App\Livewire\AccessRequests\Index as AccessRequestsIndex;
use App\Livewire\Administrators\Index as AdministratorsIndex;
use App\Livewire\AuditLogs\Index as AuditLogsIndex;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Livewire\UserMappings\Index as UserMappingsIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Internal API for SIMRS CodeIgniter 3 Integration
|--------------------------------------------------------------------------
*/
Route::prefix('internal/v1')->middleware('throttle:60,1')->group(function () {
    Route::post('/access/verify', [AccessVerificationController::class, 'verify'])
        ->name('internal.access.verify');
});

/*
|--------------------------------------------------------------------------
| Guest Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

/*
|--------------------------------------------------------------------------
| Protected Gateway Administrative Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login');
    })->name('logout');

    Route::get('/', Dashboard::class)->name('dashboard');

    // Access Management Routes (Access Admin & Super Admin)
    Route::middleware('role:access_admin')->group(function () {
        Route::get('/access-requests', AccessRequestsIndex::class)->name('access-requests.index');
        Route::get('/user-mappings', UserMappingsIndex::class)->name('user-mappings.index');
    });

    // Security Audit Routes (Security Auditor & Super Admin)
    Route::middleware('role:security_auditor')->group(function () {
        Route::get('/audit-logs', AuditLogsIndex::class)->name('audit-logs.index');
    });

    // System Administration (Super Admin only)
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/administrators', AdministratorsIndex::class)->name('administrators.index');
    });
});
