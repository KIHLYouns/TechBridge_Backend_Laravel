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

    

    

   
} 