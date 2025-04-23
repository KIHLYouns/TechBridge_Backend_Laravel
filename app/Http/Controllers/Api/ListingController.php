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




public function index()
{
    try {
        Log::info("Début de la récupération des annonces");
        
        $listings = Listing::with([
            'city:id,name',
            'category:id,name',
            'partner:id,username'
        ])->get();
        
        Log::info("Nombre d'annonces trouvées : " . $listings->count());
        
        $formattedListings = $listings->map(function ($listing) {
            try {
                return [
                    'id' => $listing->id,
                    'title' => $listing->title,
                    'description' => $listing->description,
                    'price_per_day' => $listing->price_per_day,
                    'status' => $listing->status,
                    'delivery_option' => $listing->delivery_option,
                    'created_at' => $listing->created_at,
                    'city_name' => $listing->city ? $listing->city->name : null,
                    'category_name' => $listing->category ? $listing->category->name : null,
                    'partner_username' => $listing->partner ? $listing->partner->username : null
                ];
            } catch (\Exception $e) {
                Log::error("Erreur lors du formatage de l'annonce ID " . $listing->id . ": " . $e->getMessage());
                return null;
            }
        })->filter();

        Log::info("Formatage des annonces terminé");
        return response()->json($formattedListings);
    } catch (\Exception $e) {
        Log::error("Erreur lors de la récupération des annonces : " . $e->getMessage());
        Log::error($e->getTraceAsString());
        return response()->json(['error' => 'Erreur interne du serveur.'], 500);
    }
}

public function store(Request $request)
{
try {
    Log::info(" Début de la création d'une annonce");

    // Log sans fichiers
    Log::info(" Données reçues (hors fichiers) :", $request->except('images'));

    // Validation
    $validated = $request->validate([
        'partner_id' => 'required|exists:user,id',
        'city_id' => 'required|exists:city,id',
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'price_per_day' => 'required|numeric',
        'status' => 'required|string|in:active,archived,inactive',
        'category_id' => 'required|exists:category,id',
        'delivery_option' => 'required|boolean',
        'images' => 'nullable|array',
        'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ]);

    Log::info(" Validation réussie");

    // Création de l'annonce
    $listing = Listing::create([
        'partner_id' => $validated['partner_id'],
        'city_id' => $validated['city_id'],
        'title' => $validated['title'],
        'description' => $validated['description'],
        'price_per_day' => $validated['price_per_day'],
        'status' => $validated['status'],
        'category_id' => $validated['category_id'],
        'created_at' => now(),
        'delivery_option' => $validated['delivery_option'],
    ]);

    Log::info(" Annonce créée avec l'ID : " . $listing->id);

    // Gestion des images
    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $image) {
            $path = $image->store('images', 'public');

            $imagePath = str_replace('public/', 'storage/', $path); // Convertit le chemin pour être accessible via /storage

            $listing->images()->create([
                'url' => $imagePath
            ]);

            Log::info(" Image sauvegardée : $imagePath");

        
        }
    }

    Log::info(" Création complète de l'annonce réussie");

    return response()->json($listing->load('images'), 201);

} catch (\Exception $e) {
    Log::error(" Erreur création annonce : " . $e->getMessage());
    Log::error($e->getTraceAsString());
    return response()->json(['error' => 'Erreur lors de la création de l\'annonce.'], 500);
}
}



public function show($id)
{
    $listing = Listing::findOrFail($id);
    return response()->json($listing);
}

public function update(Request $request, $id)
{
    $listing = Listing::findOrFail($id);
    $listing->update($request->all());
    return response()->json($listing);
}

public function destroy($id)
{
    $listing = Listing::findOrFail($id);
    $listing->delete();
    return response()->json(null, 204);
}
}
