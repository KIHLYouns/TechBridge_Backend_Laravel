<?php

namespace App\Models;

use Carbon\Traits\Timestamp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



class City extends Model
{
    use HasFactory;

    protected $table = 'city';


    protected $fillable = ['name'];

    public $timestamps = false;

    public function listings()
    {
        return $this->hasMany(Listing::class);
    }
}

