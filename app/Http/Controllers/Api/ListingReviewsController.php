<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\Request;

class ReviewsController extends Controller
{
    public function getReviews($listingId)
    {
        $listing = Listing::with(['reviews.reviewer'])->find($listingId);

        if (!$listing) {
            return response()->json(['error' => 'Listing not found'], 404);
        }

        $formattedReviews = $listing->reviews->map(function ($review) {
            return [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at->toIso8601String(),
                'reviewer' => [
                    'id' => $review->reviewer->id,
                    'username' => $review->reviewer->username,
                    'avatar_url' => $review->reviewer->avatar_url ??
                        "https://ui-avatars.com/api/?name={$review->reviewer->firstname}+{$review->reviewer->lastname}",
                ],
            ];
        });

        return response()->json($formattedReviews);
    }
}
