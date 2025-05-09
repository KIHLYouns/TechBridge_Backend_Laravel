<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Availability extends Model
{

    protected $table = 'availability';

    protected $fillable = [
        'listing_id',
        'start_date',
        'end_date',
    ];

    public $timestamps = false;

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }
}
