<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Listing;
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
    
            $reservation = Reservation::create([
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => 'pending',
                'client_id' => $validated['client_id'],
                'listing_id' => $validated['listing_id'],
                'partner_id' => $listing->partner_id,
                'delivery_option' => $validated['delivery_option'] ?? false,
                'created_at' => now()
            ]);
    
            return response()->json([
                'success' => true,
                'message' => 'Reservation created successfully',
                'data' => $reservation
            ], 201);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create reservation',
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

    // Update reservation
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

    // Delete reservation
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
}