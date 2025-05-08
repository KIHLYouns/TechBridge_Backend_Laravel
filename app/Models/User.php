<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public $timestamps = false;

    protected $table = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'firstname',
        'lastname',
        'email',
        'password',
        'avatar_url',
        'phone_number',
        'address',
        'role',
        'is_partner',
        'partner_rating',
        'client_rating',
        'client_reviews',
        'partner_reviews',
        'longitude',
        'latitude',
        'city_id',
        'join_date',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'partner_rating' => 'decimal:1',
        'client_rating' => 'decimal:1',
        'is_partner' => 'boolean',
        'join_date' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the city this user belongs to
     */
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get reviews received by this user
     */
    public function receivedReviews()
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }

    /**
     * Get reviews given by this user
     */
    public function givenReviews()
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    /**
     * Get listings owned by this user
     */
    public function listings()
    {
        return $this->hasMany(Listing::class, 'partner_id');
    }

    /**
     * Get reservations where this user is the client
     */
    public function clientReservations()
    {
        return $this->hasMany(Reservation::class, 'client_id');
    }

    /**
     * Get reservations where this user is the partner
     */
    public function partnerReservations()
    {
        return $this->hasMany(Reservation::class, 'partner_id');
    }
}
