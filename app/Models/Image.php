<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Image extends Model
{
    use HasFactory;


    protected $table = 'image';

    protected $fillable = [
        'listing_id',
        'url',
    ];

    public $timestamps = false;

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }
}
