<?php

namespace App\Services;

use App\Models\Review;
use Carbon\Carbon;

class ReviewService
{
    /**
     * Vérifie si une review est visible en fonction de l'existence d'une autre review
     * ou si une semaine s'est écoulée après la fin de la réservation.
     */
    public function isVisible(Review $review): bool
    {
        $otherReviewExists = Review::where('reservation_id', $review->reservation_id)
            ->where('reviewer_id', $review->reviewee_id)
            ->where('reviewee_id', $review->reviewer_id)
            ->exists();

        $oneWeekPassed = $review->reservation &&
            Carbon::parse($review->reservation->end_date)->addWeek()->lt(now());

        return $otherReviewExists || $oneWeekPassed;
    }

    
    public function formatReview(Review $review): array
    {
        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'created_at' => $review->created_at->toIso8601String(),
            'type' => $review->type ?? null,
            'reviewer' => [
                'id' => $review->reviewer->id,
                'username' => $review->reviewer->username,
                'firstname' => $review->reviewer->firstname,
                'lastname' => $review->reviewer->lastname,
                'avatar_url' => $review->reviewer->avatar_url
                    ?? "https://ui-avatars.com/api/?name={$review->reviewer->firstname}+{$review->reviewer->lastname}"
            ],
            'reviewee' => [
                'id' => $review->reviewee->id,
                'username' => $review->reviewee->username,
                'firstname' => $review->reviewee->firstname,
                'lastname' => $review->reviewee->lastname,
                'avatar_url' => $review->reviewee->avatar_url
                    ?? "https://ui-avatars.com/api/?name={$review->reviewee->firstname}+{$review->reviewee->lastname}"
            ]
        ];
    }
}
