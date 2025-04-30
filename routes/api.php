<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

// ✅ Routes API SANS auth pour tester facilement
Route::prefix('user')->group(function() {
    Route::get('/{id}', [UserController::class, 'show']);
    Route::put('/{id}', [UserController::class, 'updateById']);
    Route::post('/', [UserController::class, 'store']);
});

// Test route
Route::get('/test', function () {
    return ['message' => 'API OK'];
});

