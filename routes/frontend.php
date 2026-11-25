<?php

use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\RegistrationController;
use App\Http\Controllers\Frontend\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('frontend.home');
Route::get('privacy-policy', [HomeController::class, 'privacyPolicy'])->name('frontend.privacy');

Route::post('register', [RegistrationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('frontend.register');

// {registration:token} is a random UUID, so one walker can't open another's page.
Route::get('thank-you/{registration:token}', [RegistrationController::class, 'thankYou'])
    ->name('frontend.thankyou');

Route::post('support-ticket', [SupportTicketController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('frontend.support-ticket');
