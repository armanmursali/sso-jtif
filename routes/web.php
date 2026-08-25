<?php

use Illuminate\Support\Facades\Route;
use Jtif\SsoJtif\Http\Controllers\SsoAuthController;

Route::get('auth/sso/redirect', [SsoAuthController::class, 'redirect'])->name('sso.redirect');
Route::get('auth/sso/callback', [SsoAuthController::class, 'callback'])->name('sso.callback');