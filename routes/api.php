<?php

use App\Http\Controllers\AuthController;

Route::post('/auth/signup', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

