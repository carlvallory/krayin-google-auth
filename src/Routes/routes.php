<?php

use CarlVallory\KrayinGoogleAuth\Http\Controllers\GoogleAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/login/google', [GoogleAuthController::class, 'redirect'])->name('google-auth.redirect');
    Route::get('/login/google/callback', [GoogleAuthController::class, 'callback'])->name('google-auth.callback');
});
