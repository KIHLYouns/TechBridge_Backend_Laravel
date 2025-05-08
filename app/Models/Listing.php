<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Listing extends Model
{
    use HasFactory;

    protected $table = 'listing'; 

    protected $fillable = [
        'partner_id', 'city_id', 'title', 'description', 'price_per_day',
        'status', 'is_premium', 'premium_start_date', 'premium_end_date',
        'category_id', 'created_at', 'delivery_option',
        'equipment_rating', 'longitude', 'latitude' // Ajouts
    ];

    public $timestamps = false;

    protected $casts = [
        'price_per_day' => 'decimal:2',
        'equipment_rating' => 'decimal:1',
        'is_premium' => 'boolean',
        'premium_start_date' => 'datetime',
        'premium_end_date' => 'datetime',
        'delivery_option' => 'boolean',
        'longitude' => 'decimal:6',
        'latitude' => 'decimal:6',
    ];

    public function images()
    {
        return $this->hasMany(Image::class, 'listing_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'listing_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    /**
     * Get the reviews for this listing
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)
            ->where('type', 'forObject')
            ->where('is_visible', true);
    }

    /**
     * Get the availability periods for this listing
     */
    public function availabilities(): HasMany
    {
        return $this->hasMany(Availability::class);
    }
}
