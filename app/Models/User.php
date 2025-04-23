<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Spécifie le nom de la table (au singulier, car ta table s'appelle 'user')
    protected $table = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username', // Assure-toi que 'username' est dans ta table
        'email',
        'password',
        'phone_number',
        'address',
        'role',
        'avatar_url',
        'join_date',
        'avg_rating',
        'review_count',
        'longitude',
        'latitude',
        'city_id', // Assure-toi que 'city_id' est dans ta table
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'join_date' => 'datetime', // Assure-toi que la date est bien gérée
        ];
    }

    // Si tu souhaites définir les relations avec les autres tables, voici des exemples :
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function listings()
    {
        return $this->hasMany(Listing::class, 'partner_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}

