<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    protected $table = 'user';

    protected $fillable = [
        'username',
        'firstname',      
        'lastname',      
        'email',
      'password',
        'phone_number',
        'address',
        'role',
        'is_partner',
        'avatar_url',
        'join_date',
        'client_rating',
        'client_reviews',
        'partner_rating',
        'partner_reviews',
        'longitude',
        'latitude',
        'city_id',

    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */

     protected $casts = [
        'join_date' => 'datetime',
    ];
    protected function casts(): array
    {
        return [
            'updated_at' => 'datetime',
            'join_date' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function city()
{
    return $this->belongsTo(City::class);
}


public function reviewsAsClient()
{
    return $this->hasMany(Review::class, 'reviewee_id')->where('type', 'forClient');
}

public function reviewsAsPartner()
{
    return $this->hasMany(Review::class, 'reviewee_id')->where('type', 'forPartner');
}


}

