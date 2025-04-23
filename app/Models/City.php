<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class City extends Model
{
    use HasFactory;

    protected $table = 'city';

    protected $fillable = ['name'];

    public function listings()
    {
        return $this->hasMany(Listing::class);
    }
}
