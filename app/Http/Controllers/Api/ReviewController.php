<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Review;
use App\Models\Reservation;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReviewSubmittedMail;

class ReviewController extends Controller
{
    /**
     * Submit a new review for a partner, client, or listing
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'reviewer_id' => 'required|integer|exists:user,id',
            'reviewee_id' => 'required|integer|exists:user,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:5|max:500',
            'reservation_id' => 'required|integer|exists:reservation,id',
            'listing_id' => 'sometimes|integer|exists:listing,id',
            'type' => 'required|string|in:forPartner,forClient,forObject',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'message' => 'Invalid review data',
                'errors' => $this->formatValidationErrors($validator->errors()->toArray())
            ], 400);
        }

        // Get the reservation
        $reservation = Reservation::find($request->reservation_id);
        
        if (!$reservation) {
            return response()->json([
                'status' => 404,
                'message' => "Reservation with ID {$request->reservation_id} not found"
            ], 404);
        }
        
        // Check if the reservation is completed
        if ($reservation->status !== 'completed') {
            return response()->json([
                'status' => 400,
                'message' => 'Cannot review an incomplete reservation'
            ], 400);
        }
        
        // Check if the reviewer is part of the reservation
        if ($request->reviewer_id != $reservation->client_id && $request->reviewer_id != $reservation->partner_id) {
            return response()->json([
                'status' => 403,
                'message' => 'User is not authorized to review this reservation'
            ], 403);
        }
        
        // Check if the reviewee is part of the reservation (except for forObject reviews)
        if ($request->type !== 'forObject' && 
            $request->reviewee_id != $reservation->client_id && 
            $request->reviewee_id != $reservation->partner_id) {
            return response()->json([
                'status' => 400,
                'message' => 'Reviewee is not part of this reservation'
            ], 400);
        }
        
        // For forObject reviews, ensure a listing ID is provided
        if ($request->type === 'forObject' && !$request->has('listing_id')) {
            return response()->json([
                'status' => 400,
                'message' => 'Listing ID is required for object reviews'
            ], 400);
        }
        
        // Check if the reviewer has already submitted a review for this reservation
        $existingReview = Review::where('reviewer_id', $request->reviewer_id)
            ->where('reservation_id', $request->reservation_id)
            ->where('type', $request->type)
            ->first();
            
        if ($existingReview) {
            return response()->json([
                'status' => 400,
                'message' => 'You have already submitted a review for this reservation'
            ], 400);
        }

        // Begin transaction to ensure rating updates are atomic
        DB::beginTransaction();
        
        try {
            // Create the review
            $review = new Review();
            $review->reviewer_id = $request->reviewer_id;
            $review->reviewee_id = $request->reviewee_id;
            $review->rating = $request->rating;
            $review->comment = $request->comment;
            $review->reservation_id = $request->reservation_id;
            $review->type = $request->type;
            $review->is_visible = false; // Default to not visible
            $review->created_at = Carbon::now();
            
            // Set listing ID for object reviews
            if ($request->type === 'forObject' && $request->has('listing_id')) {
                $review->listing_id = $request->listing_id;
            }
            
            $review->save();
            $reviewee = User::find($request->reviewee_id);

            if ($reviewee && filter_var($reviewee->email, FILTER_VALIDATE_EMAIL)) {
                Mail::to($reviewee->email)->send(new ReviewSubmittedMail($review));
            }

            // Update ratings based on the review type
            $this->updateRatings($review);
            
            DB::commit();
            
            return response()->json([
                'status' => 201,
                'message' => 'Review submitted successfully',
                'review' => $review
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred while saving the review',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update ratings for the relevant entities based on the review type
     * 
     * @param Review $review
     * @return void
     */
    private function updateRatings(Review $review): void
    {
        switch ($review->type) {
            case 'forPartner':
                $this->updatePartnerRating($review->reviewee_id);
                break;
                
            case 'forClient':
                $this->updateClientRating($review->reviewee_id);
                break;
                
            case 'forObject':
                if ($review->listing_id) {
                    $this->updateListingRating($review->listing_id);
                }
                break;
        }
    }
    
    /**
     * Update the partner's rating by calculating the average of all their reviews
     * 
     * @param int $partnerId
     * @return void
     */
    private function updatePartnerRating(int $partnerId): void
    {
        $avgRating = Review::where('reviewee_id', $partnerId)
            ->where('type', 'forPartner')
            ->where('is_visible', true)
            ->avg('rating');
            
        User::where('id', $partnerId)->update([
            'partner_rating' => $avgRating ? round($avgRating, 1) : null
        ]);
    }
    
    /**
     * Update the client's rating by calculating the average of all their reviews
     * 
     * @param int $clientId
     * @return void
     */
    private function updateClientRating(int $clientId): void
    {
        $avgRating = Review::where('reviewee_id', $clientId)
            ->where('type', 'forClient')
            ->where('is_visible', true)
            ->avg('rating');
            
        User::where('id', $clientId)->update([
            'client_rating' => $avgRating ? round($avgRating, 1) : null
        ]);
    }
    
    /**
     * Update the listing's rating by calculating the average of all its reviews
     * 
     * @param int $listing_id
     * @return void
     */
    private function updateListingRating(int $listing_id): void
    {
        $avgRating = Review::where('listing_id', $listing_id)
            ->where('type', 'forObject')
            ->where('is_visible', true)
            ->avg('rating');
            
        Listing::where('id', $listing_id)->update([
            'equipment_rating' => $avgRating ? round($avgRating, 1) : null
        ]);
    }
    
    /**
     * Format validation errors to match the API spec
     * 
     * @param array $errors
     * @return array
     */
    private function formatValidationErrors(array $errors): array
    {
        $formattedErrors = [];
        
        foreach ($errors as $field => $messages) {
            foreach ($messages as $message) {
                $formattedErrors[] = [
                    'field' => $field,
                    'message' => $message
                ];
            }
        }
        
        return $formattedErrors;
    }
}