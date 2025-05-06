<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Image;

class ListingController extends Controller
{
  

public function filter(Request $request)
{
    try {
        Log::info("Début du filtrage personnalisé");

        // Début de la requête avec les relations nécessaires
        $query = Listing::with(['category:id,name', 'partner:id,username', 'city:id,name', 'images:id,listing_id,url']);

        // Filtrage par city_id si présent
        if ($request->has('city_id')) {
            $query->where('city_id', $request->city_id);
            Log::info("Filtrage par city_id : " . $request->city_id);
        }

        // Filtrage par category_id si présent
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
            Log::info("Filtrage par category_id : " . $request->category_id);
        }

        // Filtrage uniquement pour les annonces avec le statut 'active'
        $query->where('status', 'active');
        Log::info("Filtrage par statut : active");

        // Récupération des annonces filtrées
        $listings = $query->get();

        // Formatage des résultats
        $formattedListings = $listings->map(function ($listing) {
            // Vérifier si des images sont associées à l'annonce
            if ($listing->images->isEmpty()) {
                Log::info("Pas d'images pour l'annonce " . $listing->id);
            }
        
            // Vérifier l'URL de l'image
            $imageUrl = $listing->images->isNotEmpty() ? $listing->images->first()->url : null;         Log::info("Image URL for listing " . $listing->id . ": " . $imageUrl);
        
            return [
                'id' => $listing->id,
                'title' => $listing->title,
                'price_per_day' => round((float) $listing->price_per_day, 2),
                'status' => $listing->status,
                 'avg_rating' => round((float) $listing->user->avg_rating, 2),
                'review_count' => $listing->user->review_count,
                'is_premium' => (bool) $listing->is_premium,
                'delivery_option' => (bool)$listing->delivery_option,
                'city_name' => $listing->city?->name,
                'category_name' => $listing->category?->name,
                'partner_username' => $listing->partner?->username,
                'image_url' => $imageUrl,
            ];
        });
        

        // Retourner les annonces formatées
        return response()->json($formattedListings);

    } catch (\Exception $e) {
        Log::error("Erreur dans le filtrage : " . $e->getMessage());
        return response()->json(['error' => 'Erreur lors du filtrage des annonces.'], 500);
    }
}

}

    

    

   
} 