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
use App\Http\Controllers\Api\ListingReviewsController;
use App\Http\Controllers\Api\PartnerReviewsController;


Route::prefix('listings')->group(function () {
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


// ✅ Routes des profile et partner enabled 
Route::prefix('users')->group(function() {
    Route::get('/{id}/profile', [UserController::class, 'show']);
    Route::patch('/{id}/profile', [UserController::class, 'updateById']);
     Route::post('/{id}/partner/enable', [UserController::class, 'enablePartner']);
});

//Route de feature/login-logout-api
Route::post('/auth/signup', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/reservations/client/{id}', [ReservationController::class, 'getByClient']);
Route::get('/reservations/partner/{id}', [ReservationController::class, 'getByPartner']);
Route::put('/reservations/{id}/cancel', [ReservationController::class, 'cancelReservation']);
Route::put('/reservations/{id}/confirm', [ReservationController::class, 'acceptReservation']);
Route::put('/reservations/{id}/decline', [ReservationController::class, 'declineReservation']);
Route::apiResource('reservations', ReservationController::class);

// User review routes
//partner and client
Route::middleware('auth:sanctum')->get('/users/{id}/reviews', [UserReviewsController::class, 'getUserReviews']);
//Client
Route::get('/reviews/clients/{clientId}', [ClientReviewsController::class, 'getClientReviews']);
//Partner
Route::get('/reviews/partners/{partnerId}', [PartnerReviewsController::class, 'getPartnerReviews']);

Route::post('/reviews', [ReviewController::class, 'store']);

// Reviews routes
// Route::middleware('auth:sanctum')->get('/reviews/check', [ReviewCheckController::class, 'checkReview']);
Route::get('/reviews/check', [ReviewCheckController::class, 'checkReview']);


// Reviews Listing
Route::get('/listings/{listingId}/reviews', [ListingReviewsController::class, 'getReviews']);
