<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    use HasFactory;

    // Explicitly set table name if different from Laravel's convention
    protected $table = 'listing';

    protected $fillable = [
        'partner_id', // This should match the column in your database
        'city_id',
        'title',
        'description',
        'price_per_day',
        'status',
        'is_premium',
        'premium_start_date',
        'premium_end_date',
        'category_id',
        'delivery_option'
    ];

    // Relationship to User (partner)
    public function user()
    {
        return $this->belongsTo(User::class, 'partner_id');
        // If your users table has a different name, specify:
        // return $this->belongsTo(User::class, 'partner_id', 'id', 'users');
    }

    // Other relationships...
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}