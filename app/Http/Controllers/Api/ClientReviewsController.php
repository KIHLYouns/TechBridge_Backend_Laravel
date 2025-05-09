<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use App\Services\ReviewService;

class ClientReviewsController extends Controller
{
    protected ReviewService $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    public function getClientReviews(int $clientId): JsonResponse
    {
        $client = User::find($clientId);

        if (!$client) {
            return response()->json([
                'status' => 404,
                'message' => "Client with ID {$clientId} not found"
            ], 404);
        }

        $clientReviews = Review::where('reviewee_id', $clientId)
            ->where('type', 'forClient')
            ->with(['reviewer', 'reviewee', 'reservation'])
            ->get()
            ->filter(fn($r) => $this->reviewService->isVisible($r))
            ->sortByDesc('created_at')
            ->values();

        return response()->json([
            'total' => $clientReviews->count(),
            'data' => $clientReviews->map(fn($r) => $this->reviewService->formatReview($r))
        ]);
    }
}
