<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class UserReviewsController extends Controller
{
    /**
     * Get reviews for a user (both received and given)
     * 
     * @param int $id User ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserReviews(int $id): JsonResponse
    {
        // Find the user
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'timestamp' => Carbon::now()->toIso8601String(),
                'status' => 404,
                'error' => 'Not Found',
                'message' => "User with ID {$id} not found"
            ], 404);
        }
        
        // Get reviews received by this user as a partner (only visible ones)
        $receivedReviews = Review::where('reviewee_id', $id)
            ->where('is_visible', true)
            ->where(function($query) {
                $query->where('type', 'forPartner')
                      ->orWhere('type', 'forObject');
            })
            ->with(['reviewer', 'reviewee'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get reviews given by this user as a client
        $givenReviews = Review::where('reviewer_id', $id)
            ->where('is_visible', true)
            ->with(['reviewer', 'reviewee'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Format the response according to the OpenAPI spec
        $formattedReceivedReviews = $receivedReviews->map(function ($review) {
            return $this->formatReview($review);
        });
        
        $formattedGivenReviews = $givenReviews->map(function ($review) {
            return $this->formatReview($review);
        });
        
        return response()->json([
            'received_reviews' => $formattedReceivedReviews,
            'given_reviews' => $formattedGivenReviews
        ]);
    }
    
    /**
     * Format a review according to the OpenAPI specification
     * 
     * @param Review $review
     * @return array
     */
    private function formatReview(Review $review): array
    {
        return [
            'id' => $review->id,
            'reviewer' => [
                'id' => $review->reviewer->id,
                'username' => $review->reviewer->username,
                'firstname' => $review->reviewer->firstname,
                'lastname' => $review->reviewer->lastname,
                'avatar_url' => $review->reviewer->avatar_url ?? "https://ui-avatars.com/api/?name={$review->reviewer->firstname}+{$review->reviewer->lastname}"
            ],
            'reviewee' => [
                'id' => $review->reviewee->id,
                'username' => $review->reviewee->username,
                'firstname' => $review->reviewee->firstname,
                'lastname' => $review->reviewee->lastname,
                'avatar_url' => $review->reviewee->avatar_url ?? "https://ui-avatars.com/api/?name={$review->reviewee->firstname}+{$review->reviewee->lastname}"
            ],
            'rating' => $review->rating,
            'comment' => $review->comment,
            'created_at' => $review->created_at->toIso8601String(),
            'type' => $review->type
        ];
    }
}