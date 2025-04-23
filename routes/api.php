<?php
use App\Http\Controllers\Api\ListingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ImageController;

Route::prefix('listings')->group(function () {
    Route::get('/filter', [ListingController::class, 'filter']); //http://127.0.0.1:8000/api/listings/filter?city_id=1&category_id=1
    Route::get('/', [ListingController::class, 'index']);
    Route::post('/', [ListingController::class, 'store']);
    Route::get('/{id}', [ListingController::class, 'show']);
    Route::put('/{id}', [ListingController::class, 'update']);
    Route::delete('/{id}', [ListingController::class, 'destroy']);


});







// Routes des villes
Route::get('/cities', [CityController::class, 'index']);
Route::get('/cities/{id}', [CityController::class, 'show']);

// Routes des catégories
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

// Route::get('/images', [ImageController::class, 'index']);
// Route::get('/image/{id}', [ImageController::class, 'show']);
