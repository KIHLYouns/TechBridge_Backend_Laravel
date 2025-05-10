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
    public function index(Request $request)
{
    try {
        Log::info('Début de la récupération des annonces.');

        // Créer la requête de base
        $query = Listing::with(['partner', 'city', 'images'])
            ->where('status', 'active'); // Filtrer uniquement les annonces actives

        // Appliquer les filtres dynamiques
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
            Log::info("Filtrage par category_id : " . $request->category_id);
        }

        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
            Log::info("Filtrage par city_id : " . $request->city_id);
        }

        if ($request->filled('min_price')) {
            $query->where('price_per_day', '>=', $request->min_price);
            Log::info("Filtrage par min_price : " . $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price_per_day', '<=', $request->max_price);
            Log::info("Filtrage par max_price : " . $request->max_price);
        }

        if ($request->filled('equipment_rating')) {
            $query->where('equipment_rating', '>=', $request->equipment_rating);
            Log::info("Filtrage par equipment_rating : " . $request->equipment_rating);
        }

        if ($request->filled('partner_rating')) {
            $query->whereHas('partner', function ($q) use ($request) {
                $q->where('partner_rating', '>=', $request->partner_rating);
            });
            Log::info("Filtrage par partner_rating : " . $request->partner_rating);
        }

        // Exécuter la requête
        $listings = $query->get();
        Log::info('Annonces récupérées avec succès.', ['count' => $listings->count()]);

        // Traitement des résultats
        $result = $listings->map(function ($listing) {
            try {
                Log::info('Traitement d\'une annonce.', ['listing_id' => $listing->id]);

                $firstImage = $listing->images->first();
                $mainImageUrl = $firstImage ? asset('storage/' . $firstImage->url) : null;

                Log::info('Image principale récupérée.', ['main_image' => $mainImageUrl]);

                $partner = $listing->partner;
                $city = $listing->city;

                return [
                    'id'             => $listing->id,
                    'title'          => $listing->title,
                    'price_per_day'  => $listing->price_per_day,
                    'is_premium'     => $listing->is_premium,
                    'equipment_rating' => $listing->equipment_rating,
                    'main_image'     => $mainImageUrl,
                    'partner'        => $partner ? [
                        'id'             => $partner->id,
                        'username'       => $partner->username,
                        'avatar_url'     => $partner->avatar_url,
                        'partner_rating' => $partner->partner_rating,
                        'partner_reviews'=> $partner->partner_reviews,
                        'coordinates'    => [
                            'latitude'    => $partner->latitude,
                            'longitude'   => $partner->longitude,
                        ],
                        'city' => $city ? [
                            'id'   => $city->id,
                            'name' => $city->name,
                        ] : null,
                    ] : null,
                ];
            } catch (\Exception $e) {
                Log::error('Erreur lors du traitement d\'une annonce.', [
                    'listing_id' => $listing->id ?? null,
                    'message' => $e->getMessage()
                ]);
                return null;
            }
        })->filter();

        Log::info('Toutes les annonces actives ont été traitées avec succès.');

        return response()->json($result, 200);
    } catch (\Exception $e) {
        Log::error('Erreur lors de la récupération des annonces :', ['message' => $e->getMessage()]);
        return response()->json([
            'error' => 'Erreur lors de la récupération des annonces'
        ], 500);
    }
}

    


public function store(Request $request)
{
try {
Log::info("Début de la création d'une annonce");
Log::info("Données reçues (hors fichiers) :", $request->except('images'));


    // Validation des données
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
        'availabilities' => 'nullable|array',
        'availabilities.*.start_date' => 'required_with:availabilities|date',
        'availabilities.*.end_date' => 'required_with:availabilities|date|after_or_equal:availabilities.*.start_date',
        'is_premium' => 'required|boolean',
        'premium_duration' => 'nullable|integer|min:1|required_if:is_premium,true',
    ]);

    Log::info("Données validées : " . json_encode($validated));

    // Vérification du nombre d'annonces actives
    $activeListingsCount = Listing::where('partner_id', $validated['partner_id'])
        ->where('status', 'active')
        ->count();
    
    Log::info("Nombre d'annonces actives du partenaire : $activeListingsCount");

    if ($validated['status'] === 'active' && $activeListingsCount >= 5) {
        // Forcer le statut à inactive
        $validated['status'] = 'inactive';
        $statusMessage = "Le partenaire a déjà 5 annonces actives. Cette annonce a été créée avec le statut 'inactive'.";
        Log::info("Statut modifié en 'inactive' en raison du nombre d'annonces actives.");
    } else {
        $statusMessage = "Annonce créée avec succès.";
    }

    // Données pour créer l'annonce
    $listingData = [
        'partner_id' => $validated['partner_id'],
        'city_id' => $validated['city_id'],
        'title' => $validated['title'],
        'description' => $validated['description'],
        'price_per_day' => $validated['price_per_day'],
        'status' => $validated['status'], // statut potentiellement modifié
        'category_id' => $validated['category_id'],
        'delivery_option' => $validated['delivery_option'],
        'is_premium' => $validated['is_premium'],
        'created_at' => now(),
    ];

    // Si l'annonce est premium, ajouter les dates de début et de fin premium
    if ((int) $validated['is_premium'] === 1) {
        $duration = (int) $validated['premium_duration'];
        $listingData['premium_start_date'] = now();
        $listingData['premium_end_date'] = now()->addDays($duration);
        Log::info("Annonce premium, durée : $duration jours, dates définies.");
    }

    // Création de l'annonce
    $listing = Listing::create($listingData);
    Log::info("Annonce créée avec ID : " . $listing->id);

    // Sauvegarde des images
    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $image) {
            Log::info("Téléchargement de l'image : " . $image->getClientOriginalName());
            $path = $image->store('images', 'public');
            $imagePath = str_replace('public/', 'storage/', $path);
            $listing->images()->create(['url' => $imagePath]);
            Log::info("Image sauvegardée : $imagePath");
        }
    } else {
        Log::info("Aucune image reçue.");
    }

    // Sauvegarde des disponibilités
    if (!empty($validated['availabilities'])) {
        foreach ($validated['availabilities'] as $availability) {
            Log::info("Enregistrement de la disponibilité : " . json_encode($availability));
            $listing->availabilities()->create([
                'start_date' => $availability['start_date'],
                'end_date' => $availability['end_date'],
            ]);
        }
    } else {
        Log::info("Aucune disponibilité reçue.");
    }

    // Retourner la réponse JSON avec les images traitées et URLs modifiées
    $listingWithImages = $listing->load(['images', 'availabilities']);

    // Appliquer la logique pour les URLs des images
    foreach ($listingWithImages->images as $image) {
        $image->url = asset('storage/' . $image->url); // Appliquer l'URL complète pour chaque image
    }

    return response()->json([
        'message' => $statusMessage,
        'listing' => $listingWithImages,
    ], 201);

} catch (\Illuminate\Validation\ValidationException $e) {
    Log::error("Erreur de validation : " . json_encode($e->errors()));
    return response()->json(['errors' => $e->errors()], 422);
} catch (\Illuminate\Database\QueryException $e) {
    Log::error("Erreur base de données : " . $e->getMessage());
    return response()->json(['error' => 'Erreur lors de la création de l\'annonce en base de données.'], 500);
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
        'equipment_rating' => $listing->equipment_rating,
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
                'url' => asset('storage/' . $img->url),  // Application de la logique ici
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



public function getListingsByPartner($partnerId)
{
try {
// Récupérer toutes les annonces du partenaire avec les relations nécessaires
$listings = Listing::with([
'city:id,name',
'partner:id,username,email,firstname,lastname,avatar_url,partner_rating,partner_reviews,longitude,latitude,city_id',
'partner.city:id,name',
'category:id,name',
'images:id,listing_id,url',
'availabilities:listing_id,start_date,end_date'
])->where('partner_id', $partnerId)->get();


    // Transformer chaque annonce dans le format souhaité
    $result = $listings->map(function ($listing) {
        return [
            'id' => $listing->id,
            'title' => $listing->title,
            'description' => $listing->description,
            'price_per_day' => $listing->price_per_day,
            'status' => $listing->status,
            'equipment_rating' => $listing->equipment_rating,
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
                    'url' => asset('storage/' . $img->url),  // Application de la logique ici
                ];
            }),
            'availabilities' => $listing->availabilities->map(function ($a) {
                return [
                    'listing_id' => $a->listing_id,
                    'start_date' => $a->start_date,
                    'end_date' => $a->end_date,
                ];
            }),
        ];
    });

    return response()->json($result, 200);

} catch (\Exception $e) {
    Log::error("Erreur lors de la récupération des annonces du partenaire : " . $e->getMessage());
    return response()->json(['error' => 'Erreur lors de la récupération des annonces'], 500);
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

public function toggleStatus($id)
{
    try {
        $listing = Listing::findOrFail($id);

        // Vérifie si on veut activer l'annonce
        if ($listing->status === 'inactive') {
            // Compter les autres annonces actives de ce partenaire
            $activeCount = Listing::where('partner_id', $listing->partner_id)
                                  ->where('status', 'active')
                                  ->count();

            if ($activeCount >= 5) {
                return response()->json([
                    'error' => 'Ce partenaire a déjà 5 annonces actives. Vous devez en désactiver une avant d’en activer une autre.'
                ], 403);
            }

            $listing->status = 'active';
        } else {
            // Sinon, on la désactive
            $listing->status = 'inactive';
        }

        $listing->save();

        return response()->json([
            'message' => 'Statut de l\'annonce mis à jour avec succès.',
            'status'  => $listing->status
        ], 200);

    } catch (\Exception $e) {
        Log::error("Erreur lors du changement de statut de l'annonce : " . $e->getMessage());
        return response()->json(['error' => 'Impossible de modifier le statut de l\'annonce.'], 500);
    }
}



public function toggleArchivedStatus($id)
{
    try {
        $listing = Listing::findOrFail($id);
        $currentStatus = $listing->status;

        if ($currentStatus === 'archived') {
            // Désarchivage : on veut la remettre active si possible
            $activeCount = Listing::where('partner_id', $listing->partner_id)
                                  ->where('status', 'active')
                                  ->count();

            if ($activeCount >= 5) {
                $listing->status = 'inactive';
                $listing->save();

                return response()->json([
                    'warning' => 'Le partenaire a déjà 5 annonces actives. L’annonce a été désarchivée en tant qu’"inactive".'
                ], 403);
            }

            $listing->status = 'active';
            $listing->save();

            return response()->json([
                'message' => 'L’annonce a été désarchivée avec succès (statut : active).'
            ], 200);
        }

        // Si l’annonce est active ou inactive, on l’archive
        if (in_array($currentStatus, ['active', 'inactive'])) {
            $listing->status = 'archived';
            $listing->save();

            return response()->json([
                'message' => 'L’annonce a été archivée avec succès.'
            ], 200);
        }

        // Autres statuts (non attendus)
        return response()->json([
            'error' => 'Statut de l’annonce non reconnu pour cette opération.'
        ], 400);

    } catch (\Exception $e) {
        Log::error("Erreur lors du changement de statut (archivage) : " . $e->getMessage());
        return response()->json(['error' => 'Erreur serveur.'], 500);
    }
}

    

}


