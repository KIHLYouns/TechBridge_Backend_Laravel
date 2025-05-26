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

        // Received reviews as partner (client reviews about partner)
        $receivedAsPartner = Review::where('reviewee_id', $id)
            ->where('type', 'forPartner')
            ->where('is_visible', true)
            ->with(['reviewer', 'reviewee', 'reservation'])
            ->get()
            ->filter(function ($review) {
                if (!$review->reservation) return false;
                // Reciprocal review: partner (reviewee) must have reviewed the client (reviewer)
                $reciprocalExists = Review::where('reservation_id', $review->reservation_id)
                    ->where('reviewer_id', $review->reviewee_id)
                    ->where('reviewee_id', $review->reviewer_id)
                    ->exists();
                $oneWeekPassed = Carbon::parse($review->reservation->end_date)->addWeek()->lt(now());
                return $reciprocalExists || $oneWeekPassed;
            })
            ->sortByDesc('created_at')
            ->values();

        // Received reviews as client (partner reviews about client)
        $receivedAsClient = Review::where('reviewee_id', $id)
            ->where('type', 'forClient')
            ->where('is_visible', true)
            ->with(['reviewer', 'reviewee', 'reservation'])
            ->get()
            ->filter(function ($review) {
                if (!$review->reservation) return false;
                // Reciprocal review: client (reviewee) must have reviewed the partner (reviewer)
                $reciprocalExists = Review::where('reservation_id', $review->reservation_id)
                    ->where('reviewer_id', $review->reviewee_id)
                    ->where('reviewee_id', $review->reviewer_id)
                    ->exists();
                $oneWeekPassed = Carbon::parse($review->reservation->end_date)->addWeek()->lt(now());
                return $reciprocalExists || $oneWeekPassed;
            })
            ->sortByDesc('created_at')
            ->values();

        $givenReviews = Review::where('reviewer_id', $id)
            ->with(['reviewer', 'reviewee'])
            ->orderBy('created_at', 'desc')
            ->get();

        $givenAsClient = $givenReviews->filter(fn($r) => $r->type === 'forPartner')->values();
        $givenAsPartner = $givenReviews->filter(fn($r) => $r->type === 'forClient')->values();

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