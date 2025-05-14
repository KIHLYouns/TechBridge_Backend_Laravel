<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    use HasFactory;

    protected $table = 'image';

    protected $fillable = [
        'listing_id',
        'url', // Stocke le chemin relatif, ex: 'images/nom_fichier.jpg'
    ];

    public $timestamps = false;

        public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

        public function getFullUrlAttribute(): ?string
    {
        if ($this->url) {
            // 'public' est le nom du disque configuré dans config/filesystems.php
            // Storage::url() génère une URL complète.
            return asset('storage/'.$this->url);
        }
        return null;
    }

        protected $appends = ['full_url'];
}