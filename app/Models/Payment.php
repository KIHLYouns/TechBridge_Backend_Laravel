<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payment extends Model
{
    protected $table = 'payment'; // Table spécifique

    public $timestamps = false; // Désactive les timestamps

    protected $fillable = [
        'amount',
        'commission_fee',
        'partner_payout',
        'payment_date',
        'status',
        'payment_method',
        'transaction_id',
        'client_id',
        'reservation_id',
        'partner_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->transaction_id)) {
                // Génère un nombre aléatoire de 4 chiffres unique
                do {
                    $id = mt_rand(1000, 9999);
                } while (self::where('transaction_id', $id)->exists());

                $payment->transaction_id = $id;
            }
        });
    }

    // Relations
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}
