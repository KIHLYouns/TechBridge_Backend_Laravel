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
            $query = Listing::with(['partner.city', 'city', 'images'])
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

                  $firstImageFullUrl = $listing->images->first()->full_url ?? null;

                    Log::info('Image principale récupérée.', ['main_image' => $firstImageFullUrl]);

                    $partner = $listing->partner;
                    $city = $listing->city;

                    return [
                        'id'             => $listing->id,
                        'title'          => $listing->title,
                        'price_per_day'  => $listing->price_per_day,
                        'is_premium'     => $listing->is_premium,
                        'equipment_rating' => $listing->equipment_rating,
                        'main_image'     => $firstImageFullUrl,
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
        Log::debug("Payload complet store (sauf fichiers):", $request->except(['new_images[]', 'new_images']));

        // Validation des données
        $validated = $request->validate([
            'partner_id' => 'required|exists:user,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price_per_day' => 'required|numeric|min:0.01',
            'category_id' => 'required|exists:category,id',
            'delivery_option' => 'required|boolean',
            'new_images' => 'required|array|min:1|max:5',
            'new_images.*' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'availabilities' => 'nullable|array',
            'availabilities.*.start_date' => 'required_with:availabilities|date_format:Y-m-d',
            'availabilities.*.end_date' => 'required_with:availabilities|date_format:Y-m-d|after_or_equal:availabilities.*.start_date',
            'is_premium' => 'required|boolean',
            'premium_duration' => 'nullable|integer|in:7,15,30|required_if:is_premium,true',
        ]);

        Log::info("Données validées pour création : " . json_encode($validated));

        $status = 'active';
        $activeListingsCount = Listing::where('partner_id', $validated['partner_id'])
            ->where('status', 'active')
            ->count();

        if ($activeListingsCount >= 5) {
            $status = 'inactive';
            $statusMessage = "Le partenaire a déjà 5 annonces actives. Cette annonce a été créée avec le statut 'inactive'.";
        } else {
            $statusMessage = "Annonce créée avec succès.";
        }

        $listingData = [
            'partner_id' => $validated['partner_id'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'price_per_day' => $validated['price_per_day'],
            'status' => $status,
            'category_id' => $validated['category_id'],
            'delivery_option' => $validated['delivery_option'],
            'is_premium' => $validated['is_premium'],
            'created_at' => now(),
        ];

        if ($validated['is_premium']) {
            $duration = (int) $validated['premium_duration'];
            $listingData['premium_start_date'] = now();
            $listingData['premium_end_date'] = now()->addDays($duration);
        }

        $listing = Listing::create($listingData);

        // Stockage des images et génération des URL
        if ($request->hasFile('new_images')) {
            foreach ($request->file('new_images') as $imageFile) {
                // Stocker l'image et obtenir le chemin relatif
                $path = $imageFile->store('images', 'public');

                // Générer l'URL complète de l'image
                // $url = asset('storage/' . $path);

                // Enregistrer l'image dans la base de données
                $listing->images()->create(['url' => $path]);

                // Enregistrer aussi dans la table 'images' si nécessaire
                // Image::create([
                //     'listing_id' => $listing->id,
                //     'url' => $path, // Chemin relatif
                // ]);

                // Log::info('Image URL: ' . $url);
            }
        }

        // Stockage des disponibilités
        if (!empty($validated['availabilities'])) {
            foreach ($validated['availabilities'] as $availability) {
                $listing->availabilities()->create([
                    'start_date' => $availability['start_date'],
                    'end_date' => $availability['end_date'],
                ]);
            }
        }

        $listing->load(['images', 'availabilities', 'category', 'city', 'partner.city']);
        return response()->json([
            'message' => $statusMessage,
            'listing' => $listing,
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error("Erreur de validation lors de la création : " . json_encode($e->errors()));
        return response()->json(['message' => 'Erreur de validation', 'errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        Log::error("Erreur création annonce : " . $e->getMessage());
        return response()->json(['message' => 'Erreur lors de la création de l\'annonce.'], 500);
    }
}


    public function show($id)
    {
        try {
            $listing = Listing::with([
                'city:id,name',
                'partner:id,username,email,firstname,lastname,avatar_url,partner_rating,partner_reviews,longitude,latitude,city_id',
                'partner.city:id,name',
                'category:id,name',
                'images',
                'availabilities:listing_id,start_date,end_date'
            ])->findOrFail($id);

            return response()->json($listing);
        } catch (\Exception $e) {
            Log::error("Erreur lors de la récupération de l'annonce $id : " . $e->getMessage());
            return response()->json(['error' => 'Annonce non trouvée'], 404);
        }
    }

    public function getListingsByPartner($partnerId)
    {
        try {
            $listings = Listing::with([
                'city:id,name',
                'partner:id,username,avatar_url,partner_rating,partner_reviews,city_id',
                'partner.city:id,name',
                'category:id,name',
                'images',
                'availabilities:listing_id,start_date,end_date'
            ])->where('partner_id', $partnerId)->get();

            return response()->json($listings, 200);

        } catch (\Exception $e) {
            Log::error("Erreur lors de la récupération des annonces du partenaire $partnerId : " . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la récupération des annonces'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        Log::info("Début de la mise à jour de l'annonce ID: $id");
        Log::debug("Payload complet update (sauf fichiers):", $request->except(['new_images[]', 'new_images', 'deleted_images[]', 'deleted_images']));

        try {
            $listing = Listing::with('images')->findOrFail($id);

            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'price_per_day' => 'sometimes|numeric|min:0.01',
                'status' => ['sometimes', 'string', \Illuminate\Validation\Rule::in(['active', 'archived', 'inactive'])],
                'category_id' => 'sometimes|integer|exists:category,id',
                'city_id' => 'sometimes|integer|exists:city,id',
                'delivery_option' => 'sometimes|boolean',
                'is_premium' => 'sometimes|boolean',
                'premium_duration' => ['nullable', 'integer', \Illuminate\Validation\Rule::in([7, 15, 30]), 'required_if:is_premium,true'],
                'deleted_images' => 'nullable|array',
                'deleted_images.*' => 'integer|exists:image,id,listing_id,' . $listing->id,
                'new_images' => 'nullable|array',
                'new_images.*' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
                'availabilities' => 'sometimes|array',
                'availabilities.*.start_date' => 'required_with:availabilities|date_format:Y-m-d',
                'availabilities.*.end_date' => 'required_with:availabilities|date_format:Y-m-d|after_or_equal:availabilities.*.start_date',
            ]);

            $deletedImagesCount = isset($validated['deleted_images']) ? count($validated['deleted_images']) : 0;
            $currentImageCount = $listing->images->count();
            $imagesAfterDeletion = $currentImageCount - $deletedImagesCount;
            $newImagesCount = $request->hasFile('new_images') ? count($request->file('new_images')) : 0;

            if (($imagesAfterDeletion + $newImagesCount) > 5) {
                return response()->json(['message' => 'Le nombre total d\'images ne peut pas dépasser 5.'], 422);
            }
            if (($imagesAfterDeletion + $newImagesCount) < 1) {
                return response()->json(['message' => 'L\'annonce doit avoir au moins une image.'], 422);
            }

            $listingUpdateData = collect($validated)->except(['deleted_images', 'new_images', 'availabilities', 'premium_duration', 'is_premium'])->toArray();

            if ($request->has('is_premium')) {
                if ($request->boolean('is_premium')) {
                    $duration = isset($validated['premium_duration']) ? (int)$validated['premium_duration'] : ($listing->premium_duration ?? 7);
                    $listingUpdateData['is_premium'] = true;
                    $listingUpdateData['premium_start_date'] = $listing->premium_start_date ?? now();
                    $listingUpdateData['premium_end_date'] = ($listingUpdateData['premium_start_date'] instanceof \Carbon\Carbon ? $listingUpdateData['premium_start_date'] : new \Carbon\Carbon($listingUpdateData['premium_start_date']))->addDays($duration);
                } else {
                    $listingUpdateData['is_premium'] = false;
                    $listingUpdateData['premium_start_date'] = null;
                    $listingUpdateData['premium_end_date'] = null;
                }
            }

            $listing->update($listingUpdateData);

            if (!empty($validated['deleted_images'])) {
                $imagesToDelete = Image::where('listing_id', $listing->id)->whereIn('id', $validated['deleted_images'])->get();
                foreach ($imagesToDelete as $image) {
                    Storage::disk('public')->delete($image->url);
                    $image->delete();
                }
            }

            if ($request->hasFile('new_images')) {
                foreach ($request->file('new_images') as $imageFile) {
                    $path = $imageFile->store('images', 'public');
                    $listing->images()->create(['url' => $path]);
                }
            }

            if (isset($validated['availabilities'])) {
                $listing->availabilities()->delete();
                foreach ($validated['availabilities'] as $availabilityData) {
                    $listing->availabilities()->create($availabilityData);
                }
            }

            $listing->refresh()->load(['images', 'availabilities', 'category', 'city', 'partner.city']);
            return response()->json($listing, 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("Erreur de validation MAJ annonce ID: $id : " . json_encode($e->errors()));
            return response()->json(['message' => 'Erreur de validation', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error("Erreur serveur MAJ annonce ID: $id : " . $e->getMessage());
            return response()->json(['message' => 'Erreur serveur lors de la mise à jour.'], 500);
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
                        'warning' => 'Vous avez déjà 5 annonces actives. Vous devez en désactiver une avant d’en activer une autre.'
                    ], 200);
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
            $newStatus = $currentStatus; // Initialiser avec le statut actuel

            if ($currentStatus === 'archived') {
                // Désarchivage : on veut la remettre active si possible
                $activeCount = Listing::where('partner_id', $listing->partner_id)
                                      ->where('status', 'active')
                                      ->count();

                if ($activeCount >= 5) {
                    $newStatus = 'inactive'; // Définir le nouveau statut
                    $listing->status = $newStatus;
                    $listing->save();

                    return response()->json([
                        'status'  => $newStatus, // Ajouter le statut à la réponse
                        'warning' => 'Vous avez déjà 5 annonces actives. L’annonce a été désarchivée en tant qu’"inactive".',
                        'message' => 'L’annonce a été désarchivée en tant qu’"inactive".' // Message optionnel pour la cohérence
                    ], 200); // OK, même avec un avertissement
                }

                $newStatus = 'active'; // Définir le nouveau statut
                $listing->status = $newStatus;
                $listing->save();

                return response()->json([
                    'status'  => $newStatus, // Ajouter le statut à la réponse
                    'message' => 'L’annonce a été désarchivée avec succès (statut : active).'
                ], 200);
            }

            // Si l’annonce est active ou inactive, on l’archive
            if (in_array($currentStatus, ['active', 'inactive'])) {
                $newStatus = 'archived'; // Définir le nouveau statut
                $listing->status = $newStatus;
                $listing->save();

                return response()->json([
                    'status'  => $newStatus, // Ajouter le statut à la réponse
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


