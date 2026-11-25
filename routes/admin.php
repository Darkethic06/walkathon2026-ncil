<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RegistrationController;
use App\Http\Controllers\Admin\ScanController;
use App\Http\Controllers\Admin\SubUserController;
use App\Http\Controllers\Admin\SupportTicketController;
use App\Support\Roles;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'admin', 'as' => 'admin.'], function () {

    Route::get('login', [AuthController::class, 'login'])->name('login');
    Route::post('login-post', [AuthController::class, 'loginPost'])->name('login.post');

    Route::middleware(['auth', 'active'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        // Admins and sub-users (volunteers)
        Route::middleware(Roles::middleware(Roles::PANEL))->group(function () {
            Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

            Route::get('registrations', [RegistrationController::class, 'index'])->name('registrations');
            Route::get('registrations/{registration}', [RegistrationController::class, 'show'])->whereNumber('registration')->name('registrations.show');
            Route::post('registrations/{registration}/attend', [RegistrationController::class, 'attend'])->whereNumber('registration')->name('registrations.attend');

            Route::get('scan', [ScanController::class, 'index'])->name('scan');
        });

        // Admins only
        Route::middleware(Roles::middleware(Roles::ADMINS))->group(function () {
            Route::get('registrations/export', [RegistrationController::class, 'export'])->name('registrations.export');
            Route::get('registrations/create', [RegistrationController::class, 'create'])->name('registrations.create');
            Route::post('registrations', [RegistrationController::class, 'store'])->name('registrations.store');
            Route::post('registrations/{registration}/unattend', [RegistrationController::class, 'unattend'])->whereNumber('registration')->name('registrations.unattend');
            Route::get('support-tickets', [SupportTicketController::class, 'index'])->name('supporttickets');
        });

        // Super admin only
        Route::middleware(Roles::middleware([Roles::SUPER_ADMIN]))->group(function () {
            Route::resource('sub-users', SubUserController::class)
                ->except('show')
                ->parameters(['sub-users' => 'subUser'])
                ->names('subusers');
        });
    });
});
