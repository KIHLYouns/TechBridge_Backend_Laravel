<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReservationController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/reservations/client/{id}', [ReservationController::class, 'getByClient']);
Route::get('/reservations/partner/{id}', [ReservationController::class, 'getByPartner']);
Route::apiResource('reservations', ReservationController::class);
