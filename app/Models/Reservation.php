<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $table = 'reservation'; // Laravel attend "reservations" par défaut

    protected $fillable = [
        'start_date',
        'end_date',
        'status',
        'contract_url',
        'delivery_option',
        'client_id',
        'partner_id',
        'listing_id',
        'created_at',
    ];

    public $timestamps = false; // si tu n’as pas de updated_at et created_at gérés automatiquement

    // Relations
    public function listing()
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }
}
