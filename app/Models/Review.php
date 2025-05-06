<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Review extends Model
{
    use HasFactory;

    protected $table = 'review';

    protected $fillable = [
        'reservation_id',
        'rating',
        'comment',
        'is_visible',
        'created_at',
        'type',
        'reviewer_id',
        'reviewee_id',
        'listing_id',
    ];

    public $timestamps = false;

    // Relations

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewee()
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }
}
