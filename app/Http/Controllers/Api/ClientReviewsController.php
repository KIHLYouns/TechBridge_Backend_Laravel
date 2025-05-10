<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class ClientReviewsController extends Controller
{
    /**
     * Get all visible reviews for a client (by equipment partners)
     *
     * @param int $clientId
     * @return \Illuminate\Http\JsonResponse
     */
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
            ->where('type', 'forClient') // très important pour filtrer les reviews de client uniquement
            ->with(['reviewer', 'reservation']) // on récupère les relations nécessaires
            ->get()
            ->filter(function ($review) {
                $otherReviewExists = Review::where('reservation_id', $review->reservation_id)
                    ->where('reviewer_id', $review->reviewee_id)
                    ->where('reviewee_id', $review->reviewer_id)
                    ->exists();

                $oneWeekPassed = $review->reservation && Carbon::parse($review->reservation->end_date)->addWeek()->lt(now());

                return $otherReviewExists || $oneWeekPassed;
            })
            ->sortByDesc('created_at')
            ->values();

        $formatted = $clientReviews->map(function ($review) {
            return [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at->toIso8601String(),
                'reviewer' => [
                    'id' => $review->reviewer->id,
                    'username' => $review->reviewer->username,
                    'avatar_url' => $review->reviewer->avatar_url ?? "https://ui-avatars.com/api/?name={$review->reviewer->firstname}+{$review->reviewer->lastname}"
                ]
            ];
        });

        return response()->json([
            'total' => $formatted->count(),
            'data' => $formatted
        ]);
    }
}
