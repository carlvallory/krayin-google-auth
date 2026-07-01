<?php

use CarlVallory\KrayinGoogleAuth\Http\Controllers\GoogleAuthController;
use CarlVallory\KrayinGoogleAuth\Http\Controllers\PendingUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/login/google', [GoogleAuthController::class, 'redirect'])->name('google-auth.redirect');
    Route::get('/login/google/callback', [GoogleAuthController::class, 'callback'])->name('google-auth.callback');
});

// Aprobación de usuarios pendientes — protegida por el guard admin (alias 'user' = Bouncer).
Route::middleware(['web', 'user'])->prefix('admin/google-auth')->group(function () {
    Route::get('/pending', [PendingUserController::class, 'index'])
        ->name('google-auth.pending.index');
    Route::post('/pending/{id}/approve', [PendingUserController::class, 'approve'])
        ->name('google-auth.pending.approve');
});
