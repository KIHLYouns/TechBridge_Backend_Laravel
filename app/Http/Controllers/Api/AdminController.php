<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
public function getUsers() 
{
    try {
        $users = User::where('role', 'USER')->get();

        $result = $users->map(function ($user) {
            return [
                'username'    => $user->username,
                'firstname'   => $user->firstname,
                'lastname'    => $user->lastname,
                'email'       => $user->email,
                'avatar_url'  => $user->avatar_url,
                'is_partner'  => $user->is_partner,
                'is_suspend'  => $user->is_suspend,
            ];
        });

        return response()->json($result, 200);
    } catch (\Exception $e) {
        Log::error('Erreur lors de la récupération des utilisateurs : ' . $e->getMessage());
        return response()->json(['error' => 'Erreur lors de la récupération des utilisateurs'], 500);
    }
}

    // Récupérer toutes les annonces (quel que soit le status)
    public function getAllListings(Request $request)
    {
        try {
            Log::info('Début de la récupération de toutes les annonces.');

            $query = Listing::with(['partner.city', 'city', 'images']);

            // Filtres optionnels
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }
            if ($request->filled('city_id')) {
                $query->where('city_id', $request->city_id);
            }
            if ($request->filled('min_price')) {
                $query->where('price_per_day', '>=', $request->min_price);
            }
            if ($request->filled('max_price')) {
                $query->where('price_per_day', '<=', $request->max_price);
            }
            if ($request->filled('equipment_rating')) {
                $query->where('equipment_rating', '>=', $request->equipment_rating);
            }
            if ($request->filled('partner_rating')) {
                $query->whereHas('partner', function ($q) use ($request) {
                    $q->where('partner_rating', '>=', $request->partner_rating);
                });
            }

            $listings = $query->get();

            // Traitement des annonces
            $result = $listings->map(function ($listing) {
                $firstImageFullUrl = $listing->images->first()->full_url ?? null;

                $partner = $listing->partner;
                $city = $listing->city;

                return [
                    'id'               => $listing->id,
                    'title'            => $listing->title,
                    'price_per_day'    => $listing->price_per_day,
                    'is_premium'       => $listing->is_premium,
                    'equipment_rating' => $listing->equipment_rating,
                    'status'           => $listing->status,
                    'main_image'       => $firstImageFullUrl,
                    'partner'          => $partner ? [
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
            })->filter();

            return response()->json($result, 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des annonces :', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur lors de la récupération des annonces'], 500);
        }
    }

    public function toggleUserSuspension($id)
{
    try {
        $user = User::findOrFail($id);

        // Inverser la valeur de is_suspend : 0 devient 1, 1 devient 0
        $user->is_suspend = !$user->is_suspend;
        $user->save();

        $etat = $user->is_suspend ? 'suspendu' : 'active';

        return response()->json([
            'message' => "Utilisateur $etat avec succès.",
            'etat' => $etat
        ], 200);
    } catch (\Exception $e) {
        Log::error('Erreur lors de la suspension de l’utilisateur : ' . $e->getMessage());
        return response()->json(['error' => 'Utilisateur introuvable ou erreur serveur.'], 500);
    }
}

}
