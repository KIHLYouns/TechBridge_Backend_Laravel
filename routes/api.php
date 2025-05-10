<?php


use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ListingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\Api\UserReviewsController;
use App\Http\Controllers\Api\ClientReviewsController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ReviewCheckController;



Route::prefix('listings')->group(function () {
    Route::get('/filter', [ListingController::class, 'filter']); 
    Route::get('/', [ListingController::class, 'index']);
    Route::post('/', [ListingController::class, 'store']);
    Route::get('/{id}', [ListingController::class, 'show']);
    Route::put('/{id}', [ListingController::class, 'update']);
    Route::patch('/{id}/toggle-status', [ListingController::class, 'toggleStatus']);
    Route::patch('/{id}/toggle-archived', [ListingController::class, 'toggleArchivedStatus']);
    Route::get('/partner/{partnerId}', [ListingController::class, 'getListingsByPartner']);
});


// Routes des villes
Route::get('/cities', [CityController::class, 'index']);
Route::get('/cities/{id}', [CityController::class, 'show']);

// Routes des catégories
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);


// ✅ Routes API SANS auth pour tester facilement
Route::prefix('users')->group(function() {
    Route::get('/{id}/profile', [UserController::class, 'show']);
    Route::patch('/{id}/profile', [UserController::class, 'updateById']);
});

//Route de feature/login-logout-api
Route::post('/auth/signup', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/reservations/client/{id}', [ReservationController::class, 'getByClient']);
Route::get('/reservations/partner/{id}', [ReservationController::class, 'getByPartner']);
Route::apiResource('reservations', ReservationController::class);

// User review routes
//partner
Route::middleware('auth:sanctum')->get('/users/{id}/reviews', [UserReviewsController::class, 'getUserReviews']);
//Client
Route::get('/reviews/clients/{clientId}', [ClientReviewsController::class, 'getClientReviews']);

// Review submission route
Route::post('/reviews', [ReviewController::class, 'store']);

// Reviews routes
Route::middleware('auth:sanctum')->get('/reviews/check', [ReviewCheckController::class, 'checkReview']);

// Reviews Listing
Route::get('/listings/{listing}/reviews', [\App\Http\Controllers\Api\ListingReviewsController::class, 'getReviews']);
