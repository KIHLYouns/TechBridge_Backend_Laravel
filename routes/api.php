<?php
use App\Http\Controllers\Api\ListingController;
use Illuminate\Support\Facades\Route;

Route::prefix('listings')->group(function () {
    Route::get('/', [ListingController::class, 'index']);
    Route::post('/', [ListingController::class, 'store']);
    Route::get('/{id}', [ListingController::class, 'show']);
    Route::put('/{id}', [ListingController::class, 'update']);
    Route::delete('/{id}', [ListingController::class, 'destroy']);
});
