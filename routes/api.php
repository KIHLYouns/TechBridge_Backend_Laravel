<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

// ✅ Routes API SANS auth pour tester facilement
Route::prefix('users')->group(function() {
    Route::get('/{id}/profile', [UserController::class, 'show']);
    Route::patch('/{id}/profile', [UserController::class, 'updateById']);
});

// Test route
Route::get('/test', function () {
    return ['message' => 'API OK'];
});

