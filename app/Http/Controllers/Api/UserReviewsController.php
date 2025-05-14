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

    // === RECEIVED REVIEWS ===

    // Reçues en tant que partenaire (forPartner uniquement)
    $receivedAsPartner = Review::where('reviewee_id', $id)
        ->where('type', 'forPartner')
        ->with(['reviewer', 'reviewee', 'reservation'])
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

    // Reçues en tant que client (forClient uniquement)
    $receivedAsClient = Review::where('reviewee_id', $id)
        ->where('type', 'forClient')
        ->with(['reviewer', 'reviewee'])
        ->orderBy('created_at', 'desc')
        ->get();

    // === GIVEN REVIEWS ===

    $givenReviews = Review::where('reviewer_id', $id)
        ->where('is_visible', true)
        ->with(['reviewer', 'reviewee'])
        ->orderBy('created_at', 'desc')
        ->get();

    // Séparer les données selon le type
    $givenAsClient = $givenReviews->filter(fn($r) => $r->type === 'forPartner')->values();
    $givenAsPartner = $givenReviews->filter(fn($r) => $r->type === 'forClient')->values();

    // === FORMAT & RETURN ===

    return response()->json([
        'received_reviews_as_partner' => $receivedAsPartner->map(fn($r) => $this->formatReview($r)),
        'received_reviews_as_client' => $receivedAsClient->map(fn($r) => $this->formatReview($r)),
        'given_reviews_as_client' => $givenAsClient->map(fn($r) => $this->formatReview($r)),
        'given_reviews_as_partner' => $givenAsPartner->map(fn($r) => $this->formatReview($r)),
    ]);
}

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
