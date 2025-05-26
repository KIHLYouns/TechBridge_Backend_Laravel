<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Review;

class ListingReviewsController extends Controller
{
    public function getReviews($listingId)
    {
        // Charger le listing avec ses avis et la relation reservation
        $listing = Listing::with(['reviews.reviewer', 'reviews.reservation'])->find($listingId);

        if (!$listing) {
            return response()->json(['error' => 'Listing not found'], 404);
        }

        // Filtrer les reviews selon les conditions:
        // Condition A: Le partenaire (reservation->partner_id) a aussi raté le client (type 'forClient').
        // Condition B: Au moins 7 jours se sont écoulées depuis la fin de la location.
        $filteredReviews = $listing->reviews->filter(function ($review) {
            if (!$review->reservation) {
                return false;
            }
            $reservation = $review->reservation;
            // Condition A: Check if partner review exists for this reservation.
            $partnerReviewed = Review::where('reservation_id', $reservation->id)
                ->where('reviewer_id', $reservation->partner_id)
                ->where('type', 'forClient')
                ->exists();
            // Condition B: Check if 7 days have passed since reservation end_date.
            $sevenDaysAfter = Carbon::parse($reservation->end_date)->addDays(7);
            $datePassed = Carbon::now()->gte($sevenDaysAfter);
            return ($partnerReviewed || $datePassed);
        });

        $formattedReviews = $filteredReviews->map(function ($review) {
            $reviewer = $review->reviewer;
            return [
                'id' => $review->id,
                'rating' => (float) $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at ? $review->created_at->toIso8601String() : null,
                'reviewer' => $reviewer ? [
                    'id' => $reviewer->id,
                    'username' => $reviewer->username,
                    'avatar_url' => $reviewer->avatar_url ??
                        "https://ui-avatars.com/api/?name=" . urlencode($reviewer->firstname . ' ' . $reviewer->lastname),
                ] : null,
            ];
        });

        return response()->json($formattedReviews);
    }
}