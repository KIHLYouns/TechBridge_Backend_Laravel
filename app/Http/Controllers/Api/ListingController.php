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
                'partner:id,username',
                'images:id,listing_id,url'
            ])->get();

            $formattedListings = $listings->map(function ($listing) {
                return [
                    'id' => $listing->id,
                    'title' => $listing->title,
                    'partner_name' => $listing->partner?->username,
                    'photos' => $listing->images->map(fn($img) => asset($img->url)),
                    'url_photo' => $listing->images->first() ? asset($listing->images->first()->url) : null,
                    'avg_rating' => $listing->avg_rating,
                    'prix_per' => $listing->price_per_day,
                    'city_name' => $listing->city?->name,
                    'is_premium' => $listing->is_premium,
                    'delivery_option' => $listing->delivery_option
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
            ]);

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

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('images', 'public');
                    $imagePath = str_replace('public/', 'storage/', $path);

                    $listing->images()->create([
                        'url' => $imagePath
                    ]);

                    Log::info("Image sauvegardée : $imagePath");
                }
            }

            return response()->json($listing->load('images'), 201);

        } catch (\Exception $e) {
            Log::error("Erreur création annonce : " . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json(['error' => 'Erreur lors de la création de l\'annonce.'], 500);
        }
    }

    public function show($id) 
    {
        try {
            $listing = Listing::with([
                'city:id,name',
                'partner:id,username,email',
                'category:id,name',
                'images:id,listing_id,url',
                'reservations:id,listing_id,start_date,end_date'
            ])->findOrFail($id);

            return response()->json([
                'id' => $listing->id,
                'title' => $listing->title,
                'description' => $listing->description,
                'price_per_day' => $listing->price_per_day,
                'status' => $listing->status,
                'delivery_option' => $listing->delivery_option,
                'is_premium' => $listing->is_premium,
                'avg_rating' => $listing->avg_rating,
                'review_count' => $listing->review_count,
                'created_at' => $listing->created_at,
                'city' => $listing->city?->name,
                'category' => $listing->category?->name,
                'partner' => [
                    'name' => $listing->partner?->username,
                    'email' => $listing->partner?->email,
                ],
                'photos' => $listing->images->map(fn($img) => asset('storage/' . str_replace('storage/', '', $img->url))),
                'reservations' => $listing->reservations->map(function ($reservation) {
                    return [
                        'start_date' => $reservation->start_date,
                        'end_date' => $reservation->end_date,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur lors de la récupération de l'annonce $id : " . $e->getMessage());
            return response()->json(['error' => 'Annonce non trouvée'], 404);
        }
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
