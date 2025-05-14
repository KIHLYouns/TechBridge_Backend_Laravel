<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class PartnerReviewsController extends Controller
{
    /**
     * Get all visible reviews for a partner (by clients)
     *
     * @param int $partnerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPartnerReviews(int $partnerId): JsonResponse
    {
        $partner = User::find($partnerId);

        if (!$partner) {
            return response()->json([
                'status' => 404,
                'message' => "Partner with ID {$partnerId} not found"
            ], 404);
        }

        $partnerReviews = Review::where('reviewee_id', $partnerId)
            ->where('type', 'forPartner') // très important pour filtrer les reviews du partenaire uniquement
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

        $formatted = $partnerReviews->map(function ($review) {
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
