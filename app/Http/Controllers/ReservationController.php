<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReservationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after:start_date',
                'listing_id' => 'required|exists:listing,id',
                'client_id' => 'required|exists:user,id',
                'delivery_option' => 'boolean'
            ]);
    
            $listing = Listing::find($validated['listing_id']);
    
            // Vérifier les réservations existantes pour ce listing
            $existingReservations = Reservation::where('listing_id', $validated['listing_id'])
                ->where(function($query) use ($validated) {
                    $query->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                          ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                          ->orWhere(function($q) use ($validated) {
                              $q->where('start_date', '<', $validated['start_date'])
                                ->where('end_date', '>', $validated['end_date']);
                          });
                })
                ->whereIn('status', ['pending', 'confirmed', 'ongoing'])
                ->exists();
    
            if ($existingReservations) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet objet est déjà réservé pour la période demandée',
                    'available' => false
                ], 409); 
            }
    
            $reservation = Reservation::create([
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => 'pending',
                'delivery_option' => $validated['delivery_option'] ?? false,
                'created_at' => now()
            ]);
    
            return response()->json([
                'success' => true,
                'message' => 'Réservation créée avec succès',
                'data' => $reservation,
                'available' => true
            ], 201);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Échec de la création de la réservation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function index(): JsonResponse
    {
        try {
            $reservations = Reservation::all();
            
            return response()->json([
                'success' => true,
                'message' => count($reservations) . ' reservations retrieved',
                'data' => $reservations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get reservations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $reservation = Reservation::find($id);
            
            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reservation not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Reservation found',
                'data' => $reservation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get reservation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $reservation = Reservation::find($id);
            
            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reservation not found'
                ], 404);
            }

            $validated = $request->validate([
                'status' => 'sometimes|in:pending,confirmed,canceled,completed',
                'delivery_option' => 'sometimes|boolean'
            ]);

            $reservation->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Reservation updated',
                'data' => $reservation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update reservation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $reservation = Reservation::find($id);
            
            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reservation not found'
                ], 404);
            }

            $reservation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Reservation deleted'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete reservation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getByClient($id): JsonResponse
    {
        try {
            $client = User::find($id);
    
            if (!$client) {
                \Log::error("Client not found with ID: $id");
                return response()->json(['error' => 'Client not found'], 404);
            }
    
            // Fetch reservations related to the client
            $reservations = Reservation::with([
                    'listing:id,title', 
                    'partner:id,username,email,phone_number,avatar_url', 
                    'client:id,username,email,phone_number,avatar_url'
                ])
                ->where('client_id', $id)
                ->get()
                ->each(function ($reservation) {
                    $reservation->makeHidden(['client_id', 'partner_id', 'listing_id']);
                });
    
            if ($reservations->isEmpty()) {
                return response()->json(['message' => 'No reservations found for this client'], 404);
            }
    
            return response()->json($reservations, 200);
    
        } catch (\Exception $e) {
            \Log::error('Error fetching reservations for client ID: ' . $id, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
    

    public function getByPartner($id): JsonResponse
{
    try {
        \Log::info("Fetching reservations for partner with ID: $id");

        // Check if the partner exists
        $partnerExists = User::where('id', $id)
                             ->where('role', 'USER')
                             ->where('is_partner', true)
                             ->exists();

        if (!$partnerExists) {
            \Log::error("Partner not found with ID: $id");
            return response()->json(['error' => 'Partner not found'], 404);
        }

        \Log::info("Partner found with ID: $id. Fetching reservations...");

        // Fetch reservations for the partner
        $reservations = Reservation::with([
                'listing:id,title',  
                'partner:id,username,email,phone_number,avatar_url', 
                'client:id,username,email,phone_number,avatar_url'  
            ])
            ->where('partner_id', $id)
            ->get()
            ->each(function ($reservation) {
                // Exclude the unwanted fields from the reservation model
                $reservation->makeHidden(['partner_id', 'client_id', 'listing_id']);
            });

        // Log the count of reservations fetched
        \Log::info("Number of reservations found for partner ID $id: " . $reservations->count());

        return response()->json($reservations, 200);

    } catch (\Exception $e) {
        // Log error details in case of failure
        \Log::error('Error fetching reservations for partner ID: ' . $id, [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json(['error' => 'Server error'], 500);
    }
}

    }