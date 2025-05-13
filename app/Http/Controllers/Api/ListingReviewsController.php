<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\Request;

class ListingReviewsController extends Controller
{
    public function getReviews($listingId)
    {
        // Charger le listing avec ses avis et les informations du reviewer
        $listing = Listing::with(['reviews.reviewer'])->find($listingId);

        if (!$listing) {
            return response()->json(['error' => 'Listing not found'], 404);
        }

        $formattedReviews = $listing->reviews->map(function ($review) {
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
