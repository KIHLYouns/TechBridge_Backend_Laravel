<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use App\Services\ReviewService;


class UserReviewsController extends Controller
{
    protected ReviewService $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    public function getUserReviews(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'timestamp' => Carbon::now()->toIso8601String(),
                'status' => 404,
                'error' => 'Not Found',
                'message' => "User with ID {$id} not found"
            ], 404);
        }

        $receivedReviews = Review::where('reviewee_id', $id)
            ->whereIn('type', ['forPartner', 'forObject'])
            ->with(['reviewer', 'reviewee', 'reservation'])
            ->get()
            ->filter(fn($r) => $this->reviewService->isVisible($r))
            ->sortByDesc('created_at')
            ->values();

        $givenReviews = Review::where('reviewer_id', $id)
            ->where('is_visible', true)
            ->with(['reviewer', 'reviewee'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'received_reviews' => $receivedReviews->map(fn($r) => $this->reviewService->formatReview($r)),
            'given_reviews' => $givenReviews->map(fn($r) => $this->reviewService->formatReview($r))
        ]);
    }
}