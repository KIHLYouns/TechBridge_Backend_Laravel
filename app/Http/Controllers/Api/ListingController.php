<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Image;
use App\Models\Availability;


class ListingController extends Controller

{
    public function index()
    {
        try {
            Log::info("Début de la récupération des annonces");
    
            $listings = Listing::with([
                'city:id,name',
                'partner:id,username',
                'category:id,name',
                'images:id,listing_id,url'
            ])->get();
    
            $formattedListings = $listings->map(function ($listing) {
                return [
                    'id' => $listing->id,
                    'title' => $listing->title,
                    'price_per_day' => $listing->price_per_day,
                    'status' => $listing->status,
                    'avg_rating' => $listing->avg_rating,
                    'review_count' => $listing->review_count,
                    'is_premium' => $listing->is_premium,
                    'delivery_option' => $listing->delivery_option,
                    'city_name' => $listing->city?->name,
                    'category_name' => $listing->category?->name,
                    'partner_username' => $listing->partner?->username,
                    'image_url' => $listing->images->first() ? asset($listing->images->first()->url) : null
                ];
            });
    
            return response()->json($formattedListings);
        } catch (\Exception $e) {
            Log::error("Erreur lors de la récupération des annonces : " . $e->getMessage());
            return response()->json(['error' => 'Erreur serveur'], 500);
        }
    }
    

    public function store(Request $request)
    {
        try {
            Log::info("Début de la création d'une annonce");
            Log::info("Données reçues (hors fichiers) :", $request->except('images'));
    
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
    
                // Nouveaux champs
                'availabilities' => 'nullable|array',
                'availabilities.*.start_date' => 'required_with:availabilities|date',
                'availabilities.*.end_date' => 'required_with:availabilities|date|after_or_equal:availabilities.*.start_date',
    
                'is_premium' => 'required|boolean',
                'premium_duration' => 'nullable|integer|min:1|required_if:is_premium,true',
            ]);
    
            $listingData = [
                'partner_id' => $validated['partner_id'],
                'city_id' => $validated['city_id'],
                'title' => $validated['title'],
                'description' => $validated['description'],
                'price_per_day' => $validated['price_per_day'],
                'status' => $validated['status'],
                'category_id' => $validated['category_id'],
                'delivery_option' => $validated['delivery_option'],
                'is_premium' => $validated['is_premium'],
                'created_at' => now(),
            ];
    
            if ((int) $validated['is_premium'] === 1) {
                $duration = (int) $validated['premium_duration'];
                $listingData['premium_start_date'] = now();
                $listingData['premium_end_date'] = now()->addDays($duration);
            }
            
    
            $listing = Listing::create($listingData);
    
            // Sauvegarde des images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('images', 'public');
                    $imagePath = str_replace('public/', 'storage/', $path);
                    $listing->images()->create(['url' => $imagePath]);
                    Log::info("Image sauvegardée : $imagePath");
                }
            }
    
            // Sauvegarde des disponibilités
            if (!empty($validated['availabilities'])) {
                foreach ($validated['availabilities'] as $availability) {
                    $listing->availabilities()->create([
                        'start_date' => $availability['start_date'],
                        'end_date' => $availability['end_date'],
                    ]);
                }
            }
    
            return response()->json([
                'message' => 'Annonce créée avec succès',
                'listing' => $listing->load(['images', 'availabilities']),
            ], 201);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error("Erreur création annonce : " . $e->getMessage());
            Log::error("Trace : " . $e->getTraceAsString());
            Log::error("Fichier : " . $e->getFile() . " ligne : " . $e->getLine());
        
            return response()->json(['error' => 'Erreur lors de la création de l\'annonce.'], 500);
        }
        
    }
    
    public function show($id)
{
    try {
        // Charger l'annonce avec les relations
        $listing = Listing::with([
            'city:id,name',
            'partner:id,username,email,firstname,lastname,avatar_url,partner_rating,partner_reviews,longitude,latitude,city_id',
            'partner.city:id,name',
            'category:id,name',
            'images:id,listing_id,url',
            'availabilities:listing_id,start_date,end_date'
        ])->findOrFail($id);

        // Retourner la réponse JSON avec des valeurs null si certaines relations sont absentes
        return response()->json([
            'id' => $listing->id,
            'title' => $listing->title,
            'description' => $listing->description,
            'price_per_day' => $listing->price_per_day,
            'status' => $listing->status,
            'is_premium' => $listing->is_premium,
            'premium_start_date' => $listing->premium_start_date,
            'premium_end_date' => $listing->premium_end_date,
            'created_at' => $listing->created_at,
            'delivery_option' => $listing->delivery_option,
            'category' => [
                'id' => $listing->category?->id,
                'name' => $listing->category?->name,
            ],
            'partner' => [
                'id' => $listing->partner?->id,
                'username' => $listing->partner?->username,
                'firstname' => $listing->partner?->firstname,
                'lastname' => $listing->partner?->lastname,
                'avatar_url' => $listing->partner?->avatar_url,
                'partner_rating' => $listing->partner?->partner_rating,
                'partner_reviews' => $listing->partner?->partner_reviews,
                'longitude' => $listing->partner?->longitude,
                'latitude' => $listing->partner?->latitude,
                'city' => [
                    'id' => $listing->partner?->city?->id,
                    'name' => $listing->partner?->city?->name,
                ]
            ],
            'images' => $listing->images->map(function ($img) {
                return [
                    'id' => $img->id,
                    'url' => asset($img->url),
                ];
            }),
            'availabilities' => $listing->availabilities->map(function ($a) {
                return [
                    'listing_id' => $a->listing_id,
                    'start_date' => $a->start_date,
                    'end_date' => $a->end_date,
                ];
            }),
        ]);
    } catch (\Exception $e) {
        Log::error("Erreur lors de la récupération de l'annonce : " . $e->getMessage());
        return response()->json(['error' => 'Annonce non trouvée'], 404);
    }
    
}

    

    public function update(Request $request, $id)
    {
        try {
            $listing = Listing::findOrFail($id);
    
            // Validation
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'price_per_day' => 'sometimes|numeric',
                'status' => 'sometimes|string|in:active,archived,inactive',
                'category_id' => 'sometimes|exists:category,id',
                'delivery_option' => 'sometimes|boolean',
                'deleted_images' => 'nullable|array',
                'deleted_images.*' => 'integer|exists:image,id',
                'new_images' => 'nullable|array',
                'new_images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'availabilities' => 'sometimes|array',
                'availabilities.*.start_date' => 'required_with:availabilities|date|before_or_equal:availabilities.*.end_date',
                'availabilities.*.end_date' => 'required_with:availabilities|date|after_or_equal:availabilities.*.start_date',
                'is_premium' => 'nullable|boolean',
                'premium_duration' => 'nullable|integer|in:7,15',
            ]);
    
            $listing->update($validated);
    
            // Premium
            if ($request->boolean('is_premium')) {
                $duration = $request->input('premium_duration', 7);
                $listing->premium_start_date = now();
                $listing->premium_end_date = now()->addDays($duration);
                $listing->save();
            }
    
            // Suppression des images
            if ($request->has('deleted_images')) {
                foreach ($request->input('deleted_images', []) as $imageId) {
                    $image = $listing->images()->find($imageId);
                    if ($image) {
                        Storage::disk('public')->delete(str_replace('storage/', '', $image->url));
                        $image->delete();
                    }
                }
            }
    
            // Ajout des nouvelles images
            $currentImageCount = $listing->images()->count();
    
            if ($request->hasFile('new_images')) {
                foreach ($request->file('new_images') as $image) {
                    if ($currentImageCount >= 5) {
                        return response()->json(['error' => 'La limite de 5 images a été atteinte.'], 400);
                    }
    
                    $path = $image->store('images', 'public');
                    $imagePath = str_replace('public/', 'storage/', $path);
    
                    $listing->images()->create(['url' => $imagePath]);
                    $currentImageCount++;
                }
            }
    
            // Disponibilités
            if ($request->has('availabilities')) {
                $listing->availabilities()->delete();
                foreach ($request->availabilities as $interval) {
                    if (!isset($interval['start_date'], $interval['end_date'])) {
                        return response()->json(['error' => 'Format des disponibilités invalide.'], 400);
                    }
                    $listing->availabilities()->create([
                        'start_date' => $interval['start_date'],
                        'end_date' => $interval['end_date'],
                    ]);
                }
            }
    
            return response()->json($listing->load(['images', 'availabilities']), 200);
    
        } catch (\Exception $e) {
            Log::error("Erreur lors de la mise à jour de l'annonce $id : " . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la mise à jour de l\'annonce.'], 500);
        }
    }
    
    

    public function destroy($id)
    {
        try {
            $listing = Listing::findOrFail($id);
    
          
            $listing->status = 'archived';
            $listing->save();
    
            return response()->json(['message' => 'Annonce archivée avec succès.'], 200);
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'archivage de l'annonce $id : " . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de l\'archivage de l\'annonce.'], 500);
        }
    }
    

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
