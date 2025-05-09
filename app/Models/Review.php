<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Review extends Model
{
    use HasFactory;
    public $timestamps = false;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = 'review';
    protected $fillable = [
        'reviewer_id',
        'reviewee_id',
        'reservation_id',
        'rating',
        'comment',
        'is_visible',
        'created_at',
        'type',
        'listing_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_visible' => 'boolean',
        'rating' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Get the reviewer that wrote this review
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Get the user that was reviewed
     */
    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

    /**
     * Get the reservation associated with this review
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Get the listing associated with this review (if it's a forObject review)
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * Updates review visibility based on conditions:
     * 1. Both parties have left reviews
     * 2. One week has passed since reservation end date
     * 
     * @param int $reservationId
     * @return void
     */
    public static function updateVisibility(int $reservationId): void
    {
        $reservation = Reservation::find($reservationId);
        
        if (!$reservation) {
            return;
        }
        
        // Check if both parties have left reviews
        $clientReview = self::where('reservation_id', $reservationId)
            ->where('reviewer_id', $reservation->client_id)
            ->first();
            
        $partnerReview = self::where('reservation_id', $reservationId)
            ->where('reviewer_id', $reservation->partner_id)
            ->first();
        
        // If both reviews exist, make them visible
        if ($clientReview && $partnerReview) {
            $clientReview->update(['is_visible' => true]);
            $partnerReview->update(['is_visible' => true]);

            // Trigger rating updates
            self::updateEntityRatings($clientReview);
            self::updateEntityRatings($partnerReview);
            return;
        }
        
        // If one week has passed since reservation end, make any existing reviews visible
        $oneWeekAfterEnd = Carbon::parse($reservation->end_date)->addWeek();
        
        if (Carbon::now()->gte($oneWeekAfterEnd)) {
            if ($clientReview) {
                $clientReview->update(['is_visible' => true]);
                self::updateEntityRatings($clientReview);
            }
            
            if ($partnerReview) {
                $partnerReview->update(['is_visible' => true]);
                self::updateEntityRatings($partnerReview);
            }
        }
    }

    /**
     * Update ratings for the appropriate entity based on the review type
     * 
     * @param Review $review
     * @return void
     */
    public static function updateEntityRatings(Review $review): void
    {
        // Only update ratings for visible reviews
        if (!$review->is_visible) {
            return;
        }
        
        switch ($review->type) {
            case 'forPartner':
                self::updatePartnerRating($review->reviewee_id);
                break;
                
            case 'forClient':
                self::updateClientRating($review->reviewee_id);
                break;
                
            case 'forObject':
                if ($review->listing_id) {
                    self::updateListingRating($review->listing_id);
                }
                break;
        }
    }
    
    /**
     * Update the partner's rating
     * 
     * @param int $partnerId
     * @return void
     */
    private static function updatePartnerRating(int $partnerId): void
    {
        $avgRating = self::where('reviewee_id', $partnerId)
            ->where('type', 'forPartner')
            ->where('is_visible', true)
            ->avg('rating');
            
        $reviewCount = self::where('reviewee_id', $partnerId)
            ->where('type', 'forPartner')
            ->where('is_visible', true)
            ->count();
            
        User::where('id', $partnerId)->update([
            'partner_rating' => $avgRating ? round($avgRating, 1) : null,
            'partner_reviews' => $reviewCount
        ]);
    }
    
    /**
     * Update the client's rating
     * 
     * @param int $clientId
     * @return void
     */
    private static function updateClientRating(int $clientId): void
    {
        $avgRating = self::where('reviewee_id', $clientId)
            ->where('type', 'forClient')
            ->where('is_visible', true)
            ->avg('rating');
            
        $reviewCount = self::where('reviewee_id', $clientId)
            ->where('type', 'forClient')
            ->where('is_visible', true)
            ->count();
            
        User::where('id', $clientId)->update([
            'client_rating' => $avgRating ? round($avgRating, 1) : null,
            'client_reviews' => $reviewCount
        ]);
    }
    
    /**
     * Update the listing's rating
     * 
     * @param int $listingId
     * @return void
     */
    private static function updateListingRating(int $listingId): void
    {
        $avgRating = self::where('listing_id', $listingId)
            ->where('type', 'forObject')
            ->where('is_visible', true)
            ->avg('rating');
            
        Listing::where('id', $listingId)->update([
            'equipment_rating' => $avgRating ? round($avgRating, 1) : null
        ]);
    }
}