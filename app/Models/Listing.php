<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Listing extends Model
{
    use HasFactory;

    protected $table = 'listing';

    protected $fillable = [
        'partner_id', 'city_id', 'title', 'description', 'price_per_day',
        'status', 'is_premium', 'premium_start_date', 'premium_end_date',
        'category_id', 'created_at', 'delivery_option',
    ];

    public $timestamps = false;

    public function images()
    {
        return $this->hasMany(Image::class);
    }

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }
}
