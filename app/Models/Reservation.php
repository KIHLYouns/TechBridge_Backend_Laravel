<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{

    public $timestamps = false;
    
    protected $table = 'reservation';

    protected $fillable = [
        'start_date',
        'end_date',
        'status',
        'contract_url',
        'created_at',
        'delivery_option',
        'client_id',
        'partner_id',
        'listing_id'
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'start_date' => 'datetime',
        'end_date' => 'datetime'
    ];
}