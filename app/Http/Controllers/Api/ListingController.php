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
        $listings = Listing::all();
        return response()->json($listings);
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